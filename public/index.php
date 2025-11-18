<?php
session_start();

// Define application constants
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Define Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace('/public/index.php', '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . $script_name);

// Include the main application class
require_once APP_PATH . '/core/App.php';

// Initialize and run the application
$app = new App();
$app->run();
