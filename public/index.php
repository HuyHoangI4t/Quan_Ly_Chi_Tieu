<?php

session_start();

// Define APP_PATH, CONFIG_PATH, PUBLIC_PATH
define('APP_PATH', dirname(__DIR__) . '/app');
define('CONFIG_PATH', dirname(__DIR__) . '/config');
define('PUBLIC_PATH', dirname(__DIR__) . '/public');

// Dynamically determine BASE_URL
$baseUrl = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $script_name = $_SERVER['SCRIPT_NAME']; // e.g. /myproject/public/index.php
    $base_path = str_replace('/index.php', '', $script_name);
    $public_pos = strrpos($base_path, '/public');
    if ($public_pos !== false) {
        $base_path = substr($base_path, 0, $public_pos);
    }
    if (!empty($base_path) && $base_path !== '/') {
        $baseUrl = $base_path;
    }
}
define('BASE_URL', $baseUrl); // Define it once and correctly


error_reporting(E_ALL); // Ensure error reporting is ON
ini_set('display_errors', 1); // Display errors on screen

// Composer Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php'; 

// Removed manual require_once statements as Composer autoloader handles them

$app = new App\Core\App(); // Instantiate the App class
$app->run(); // Run the application