<?php
    declare(strict_types=1);

    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    /**
     * Contract for payment gateway plugins.
     *
     * Backward-compatible: bundled and third-party plugins that do not
     * implement this interface keep working — callers still duck-type via
     * method_exists()/is_callable(). New plugins should implement it so the
     * required methods are guaranteed.
     */
    interface GatewayInterface
    {
        public function info(): array;
        public function color(): array;
        public function fields(): array;
    }

    /**
     * Base class with no-op defaults for optional gateway methods, so plugins
     * can extend it without implementing every hook the core may probe.
     */
    abstract class AbstractGateway implements GatewayInterface
    {
        public function supported_languages(): array
        {
            return [];
        }

        public function lang_text(): array
        {
            return [];
        }

        public function instructions($data = []): array
        {
            return [];
        }

        public function process_payment($data = [])
        {
        }

        public function callback($data = [])
        {
        }

        public function ipn($data = [])
        {
        }
    }

    /**
     * Load a gateway plugin class by slug.
     *
     * Centralizes the require + slug→class-name mangling + instantiation that
     * used to be duplicated across index.php, pp-functions.php, pp-adapter.php
     * and the admin UI. Returns null when the plugin cannot be loaded.
     */
    function pp_load_gateway(string $slug): ?object
    {
        $path = __DIR__ . '/../pp-modules/pp-gateways/' . $slug . '/class.php';

        if (!file_exists($path)) {
            return null;
        }

        require_once $path;

        $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $slug))) . 'Gateway';

        if (!class_exists($class)) {
            return null;
        }

        return new $class();
    }
