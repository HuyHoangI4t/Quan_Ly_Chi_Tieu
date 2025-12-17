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
// --- Maintenance mode enforcement ---
try {
    // Try reading maintenance flag from DB; if DB missing or error, default to off
    $db = (new App\Core\ConnectDB())->getConnection();
    $stmt = $db->prepare("SELECT key_name, value FROM system_settings WHERE key_name IN ('maintenance_mode')");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $maintenance = ($rows['maintenance_mode'] ?? '0') === '1';
} catch (\Throwable $e) {
    $maintenance = false;
}

if ($maintenance) {
    $sm = new \App\Core\SessionManager();
    $isAdmin = $sm->isAdmin();

    // Allow admin routes and assets. Compute relative path.
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = strtok($requestUri, '?');
    $base = BASE_URL ?: '';
    $rel = $path;
    if ($base !== '' && strpos($path, $base) === 0) {
        $rel = substr($path, strlen($base));
    }

    // Allow if admin (logged-in) or accessing admin login (/admin or /admin/*) or static assets
    $allowed = $isAdmin
        || preg_match('#^/admin#', $rel)
        || preg_match('#^/auth#', $rel)
        || preg_match('#^/(css|js|images|vendor)/#', $rel);

    if (!$allowed) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Bảo trì</title><style>body{background:#0b1220;color:#e6eef8;font-family:Inter,Arial;padding:40px;text-align:center} .box{background:#0b1227;padding:30px;border-radius:12px;display:inline-block}</style></head><body><div class="box"><h1>Hệ thống đang bảo trì</h1><p>Xin lỗi, hệ thống đang được bảo trì. Vui lòng quay lại sau.</p></div></body></html>';
        exit;
    }
}

$app->run();