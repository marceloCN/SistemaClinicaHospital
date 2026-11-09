<?php

declare(strict_types=1);

use Dotenv\Exception\ValidationException;
use Lib\Core\Database;
use Lib\Support\DbSetup;
use Lib\Support\Env;
use Lib\Support\Session;
use System\Models\Settings;

// Base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Composer autoload
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!is_file($autoload)) {
    die('Run "composer install" first.');
}

require_once $autoload;

// Environment
try {
    Env::load(BASE_PATH);
} catch (ValidationException $e) {
    // Faltan parametros requeridos del .env: panel de configuracion inicial
    // (siempre termina; en CLI la excepcion original se relanza).
    DbSetup::handle($e);
}

// Timezone
date_default_timezone_set(config('app.timezone', 'America/La_Paz'));

// Error reporting
if (config('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Session
Session::start();

// Database
Database::configure(config('database'));

// Prueba temprana de conexion: si falla (o nunca existio una conexion
// establecida) se muestra el panel de configuracion inicial, que termina
// la peticion (en CLI se relanza la excepcion original).
try {
    Database::connection();
} catch (Throwable $e) {
    DbSetup::handle($e);
}

// Setup minimum required tables
require_once BASE_PATH . '/config/setup.php';

// Load application settings into session
$settings = (new Settings())->first();

if ($settings) {
    Session::set('SITE_NAME', $settings['site_name'] ?? config('app.name'));
    Session::set('TIME_ZONE', $settings['timezone'] ?? config('app.timezone'));
    Session::set('MAINTENANCE', (bool) ($settings['maintenance_mode'] ?? false));
}

// Global shared view data
Lib\Core\View::share('appName', Session::get('SITE_NAME', config('app.name')));

// -------------------------------------------------------------------------
// Backwards compatibility with old constants (to be removed after view migration)
// -------------------------------------------------------------------------
if (!defined('URL')) {
    define('URL', config('app.url'));
}
if (!defined('APP')) {
    define('APP', 'sistema');
}
if (!defined('CHARSET')) {
    define('CHARSET', config('database.connections.' . config('database.default') . '.charset', 'utf8mb4'));
}
if (!defined('TIMEZONE')) {
    define('TIMEZONE', config('app.timezone'));
}
if (!defined('LANGUAGE')) {
    define('LANGUAGE', config('app.language'));
}

// Ensure session namespace key exists for old views/controllers
if (!Session::get('CODE')) {
    Session::set('CODE', config('app.language'));
}
if (!isset($_SESSION[constant('APP')])) {
    $_SESSION[constant('APP')] = [];
}
$_SESSION[constant('APP')]['CODE'] = Session::get('CODE');
$_SESSION[constant('APP')]['loged'] = Session::isLoggedIn();
$_SESSION[constant('APP')]['nombre'] = Session::get('nombre');
$_SESSION[constant('APP')]['id_usuario'] = Session::get('id_usuario');

// Mirror flash messages for legacy dashboard views
$flashSuccess = Session::pull('_flash_success');
if ($flashSuccess !== null) {
    $_SESSION[constant('APP')]['success'] = $flashSuccess;
}

$flashError = Session::pull('_flash_mensaje') ?? Session::pull('_flash_error');
if ($flashError !== null) {
    $_SESSION[constant('APP')]['mensaje'] = $flashError;
}
