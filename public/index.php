<?php

// Harden session cookie parameters BEFORE starting the session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Define APP_PATH, CONFIG_PATH, PUBLIC_PATH
define('APP_PATH', dirname(__DIR__) . '/app');
define('CONFIG_PATH', dirname(__DIR__) . '/config');
define('PUBLIC_PATH', dirname(__DIR__) . '/public');

// Dynamically determine BASE_URL
$baseUrl = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $script_name = $_SERVER['SCRIPT_NAME']; 
    $base_path = str_replace('/index.php', '', $script_name);
    // Remove trailing slash if it's not root
    $base_path = rtrim($base_path, '/');
    
    $public_pos = strrpos($base_path, '/public');
    if ($public_pos !== false) {
        $base_path = substr($base_path, 0, $public_pos) . '/public';
    }
    
    if (!empty($base_path) && $base_path !== '/') {
        $baseUrl = $base_path;
    }
}
define('BASE_URL', $baseUrl);

// Error Reporting Logic
// Trong Production nên set display_errors = 0
$isDev = getenv('APP_ENV') === 'development';
error_reporting($isDev ? E_ALL : 0);
ini_set('display_errors', $isDev ? 1 : 0);

// Set timezone to Vietnam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Composer Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php'; 

// Load environment variables
\App\Core\EnvLoader::load(dirname(__DIR__));

// Load constants
require_once CONFIG_PATH . '/constants.php';

$app = new App\Core\App();
$app->run();