#!/bin/sh
set -e

echo "[PipraPay] Waiting for database at ${DB_HOST:-db}:3306..."

until php -r '
    $h = getenv("DB_HOST");
    $u = getenv("DB_USER");
    $pw = getenv("DB_PASSWORD");
    try {
        new PDO("mysql:host=$h;port=3306", $u, $pw);
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
' >/dev/null 2>&1; do
    echo "[PipraPay] Database not ready, retrying in 2s..."
    sleep 2
done

echo "[PipraPay] Database is up."

# Keep the mounted storage writable by the web server (best effort on bind mounts)
chown -R www-data:www-data /var/www/html/pp-media/storage 2>/dev/null || true

if [ ! -f /var/www/html/pp-config.php ]; then
    echo "[PipraPay] No configuration found. Running automatic installation..."
    php /usr/local/bin/piprapay-install.php
else
    echo "[PipraPay] Configuration found. Skipping installation."
fi

exec apache2-foreground
