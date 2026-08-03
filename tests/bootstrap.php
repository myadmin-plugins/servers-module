<?php

/**
 * PHPUnit bootstrap for detain/myadmin-servers-module.
 *
 * Defines framework constants and stubs required to load the Plugin class
 * without the full MyAdmin runtime.
 */

// The Plugin class references the PRORATE_BILLING constant in its $settings
// array initializer. It must be defined before the class is loaded.
if (!defined('PRORATE_BILLING')) {
    define('PRORATE_BILLING', 1);
}

// Autoload the package classes via Composer's PSR-4 mapping.
// When running from the package root the vendor dir may or may not exist;
// fall back to the parent project's autoloader.
$autoloaders = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../autoload.php',
];

$loaded = false;
foreach ($autoloaders as $file) {
    if (file_exists($file)) {
        require_once $file;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    // Minimal PSR-4 registration so the test suite can still run
    spl_autoload_register(function ($class) {
        $prefix = 'Detain\\MyAdminServers\\';
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $relative = substr($class, strlen($prefix));
            $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

// Test doubles for \MyAdmin\App, \MyAdmin\Mail, \TFSmarty and \ServiceHandler.
// These let the tests invoke the plugin's lifecycle closures and assert what
// they actually did, rather than grepping src/Plugin.php for call spellings.
require_once __DIR__ . '/support/doubles.php';

/**
 * The service type table the plugin looks server names up in, keyed the way
 * run_event('get_service_types') keys it in production: by the value stored in
 * the service row's <prefix>_type column.
 */
const SERVERS_TEST_SERVICE_TYPES = [
    52 => [
        'services_id' => 52,
        'services_name' => 'Dedicated Server E3-1230',
        'services_type' => 52,
        'services_category' => 2,
    ],
];

if (!function_exists('run_event')) {
    /**
     * @param string $event
     * @param mixed $default
     * @param string $module
     * @return mixed
     */
    function run_event(string $event, $default = false, string $module = '')
    {
        if ($event === 'get_service_types') {
            return SERVERS_TEST_SERVICE_TYPES;
        }
        return $default;
    }
}

if (!function_exists('myadmin_log')) {
    /**
     * @param string $module
     * @param string $level
     * @param string $message
     * @param int|string $line
     * @param string $file
     * @param string $section
     * @param int|string $id
     * @return void
     */
    function myadmin_log(string $module, string $level, string $message, $line = '', $file = '', string $section = '', $id = ''): void
    {
        \Detain\MyAdminServers\Tests\Support\LogSpy::record($module, $level, $message, $id);
    }
}

if (!function_exists('chatNotify')) {
    /**
     * @param string $msg
     * @param string $where
     * @param array<string,mixed> $extra
     * @return void
     */
    function chatNotify(string $msg, string $where = 'notifications', array $extra = []): void
    {
        \Detain\MyAdminServers\Tests\Support\FunctionSpy::record('chatNotify', [$msg, $where, $extra]);
    }
}

if (!function_exists('setServerStatus')) {
    /**
     * @param int|string $id
     * @param string $status
     * @return void
     */
    function setServerStatus($id, string $status): void
    {
        \Detain\MyAdminServers\Tests\Support\FunctionSpy::record('setServerStatus', [$id, $status]);
    }
}

if (!function_exists('check_order_from')) {
    /**
     * @param array<string,mixed> $serverInfo
     * @return void
     */
    function check_order_from(array $serverInfo = []): void
    {
        \Detain\MyAdminServers\Tests\Support\FunctionSpy::record('check_order_from', [$serverInfo]);
    }
}

if (!function_exists('admin_email_server_pending_setup')) {
    /**
     * @param int|string $id
     * @return void
     */
    function admin_email_server_pending_setup($id): void
    {
        \Detain\MyAdminServers\Tests\Support\FunctionSpy::record('admin_email_server_pending_setup', [$id]);
    }
}

// detain/myadmin-plugin-installer (already autoloaded by the PHPUnit entry
// script) supplies the real get_module_settings() and get_module_db(). Rather
// than shadow them, feed them the globals they read so the plugin talks to the
// genuine framework helpers and gets test doubles back.
register_module(\Detain\MyAdminServers\Plugin::$module, \Detain\MyAdminServers\Plugin::$settings);
$GLOBALS[\Detain\MyAdminServers\Plugin::$module . '_dbh'] = new \Detain\MyAdminServers\Tests\Support\DbSpy();
