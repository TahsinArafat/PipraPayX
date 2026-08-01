<?php
declare(strict_types=1);

/**
 * One-shot automatic installer for the PipraPay Docker image.
 * Mirrors the web installer (pp-install): imports the bundled schema and
 * creates the admin account, then writes pp-config.php.
 */

define('PipraPay_INIT', true);

$db_host   = getenv('DB_HOST') ?: 'db';
$db_user   = getenv('DB_USER') ?: 'piprapay';
$db_pass   = getenv('DB_PASSWORD') ?: 'piprapay';
$db_name   = getenv('DB_NAME') ?: 'piprapay';
$db_prefix = getenv('DB_PREFIX') ?: 'pp_';

$appRoot = getenv('APP_ROOT') ?: '/var/www/html';

try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "[PipraPay] Cannot connect to MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$db_name`");

// Write the runtime config in the same shape the web installer writes
$configContent = "<?php
    \$db_host = '" . addslashes($db_host) . "';
    \$db_user = '" . addslashes($db_user) . "';
    \$db_pass = '" . addslashes($db_pass) . "';
    \$db_name = '" . addslashes($db_name) . "';
    \$db_prefix = '" . addslashes($db_prefix) . "';
?>";

if (file_put_contents($appRoot . '/pp-config.php', $configContent) === false) {
    fwrite(STDERR, "[PipraPay] Failed to write pp-config.php\n");
    exit(1);
}

require $appRoot . '/pp-content/pp-include/pp-functions.php';

// Import the bundled schema
$sqlContent = file_get_contents($appRoot . '/pp-content/pp-install/db.sql');
if ($sqlContent === false) {
    fwrite(STDERR, "[PipraPay] db.sql not found\n");
    exit(1);
}

if ($db_prefix !== 'pp_') {
    $sqlContent = str_replace('pp_', $db_prefix, $sqlContent);
}

foreach (splitSqlQueries($sqlContent) as $query) {
    $pdo->exec($query);
}

// Create the admin account (mirrors the web installer's admin step)
$adminName     = getenv('ADMIN_NAME') ?: 'Administrator';
$adminEmail    = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
$adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
$adminPassword = getenv('ADMIN_PASSWORD') ?: '';

if ($adminPassword === '') {
    $adminPassword = generateStrongPassword(12);
    echo "[PipraPay] ADMIN_PASSWORD not set - generated password: $adminPassword\n";

    // Persist credentials into the bind-mounted storage so no terminal is needed
    $credDir = $appRoot . '/pp-media/storage';
    if (!is_dir($credDir)) mkdir($credDir, 0755, true);
    file_put_contents(
        $credDir . '/ADMIN_CREDENTIALS.txt',
        "PipraPay admin credentials (auto-generated on first install)\n"
        . "URL:      http://<your-host>/admin\n"
        . "Username: $adminUsername\n"
        . "Password: $adminPassword\n"
        . "Set ADMIN_USERNAME / ADMIN_PASSWORD in .env to use your own, then re-provision.\n"
    );
}

$a_id = generateItemID();
$brand_id = generateItemID();
$now = getCurrentDatetime('Y-m-d H:i:s');

insertData($db_prefix . 'admin', ['a_id', 'full_name', 'username', 'email', 'password', 'temp_password', 'created_date', 'updated_date'], [$a_id, $adminName, $adminUsername, $adminEmail, password_hash($adminPassword, PASSWORD_BCRYPT), password_hash(generateStrongPassword(8), PASSWORD_BCRYPT), $now, $now]);
insertData($db_prefix . 'permission', ['brand_id', 'a_id', 'permission', 'created_date', 'updated_date'], [$brand_id, $a_id, json_encode(permissionSchema()), $now, $now]);
insertData($db_prefix . 'brands', ['brand_id', 'created_date', 'updated_date'], [$brand_id, $now, $now]);
insertData($db_prefix . 'currency', ['brand_id', 'code', 'symbol', 'created_date', 'updated_date'], [$brand_id, 'BDT', '৳', $now, $now]);

echo "[PipraPay] Installation complete.\n";
echo "[PipraPay] Login with the domain you configured in Dokploy (Domains tab).\n";
echo "[PipraPay] Username: $adminUsername\n";
echo "[PipraPay] Password: $adminPassword\n";
