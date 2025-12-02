<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Middleware Interface
 */
interface MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next);
}

/**
 * Auth Middleware - Yêu cầu đăng nhập
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login_signup');
            exit();
        }

        return $next();
    }
}

/**
 * Guest Middleware - Chỉ cho phép khách chưa đăng nhập
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }

        return $next();
    }
}

/**
 * Admin Middleware - Chỉ cho phép admin
 */
class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login_signup');
            exit();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            die('Access Denied: Admin only');
        }

        return $next();
    }
}

/**
 * CSRF Protection
 */
class CsrfProtection
{
    private static $sessionKey = 'csrf_token';
    private static $tokenLifetime = 3600;

    public static function generateToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$sessionKey] = [
            'token' => $token,
            'time' => time()
        ];

        return $token;
    }

    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION[self::$sessionKey])) {
            $tokenData = $_SESSION[self::$sessionKey];
            if ((time() - $tokenData['time']) < self::$tokenLifetime) {
                return $tokenData['token'];
            }
        }

        return self::generateToken();
    }

    public static function validateToken($token = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($token === null) {
            if (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            } elseif ($_SERVER['CONTENT_TYPE'] === 'application/json' || 
                     strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                $json = json_decode(file_get_contents('php://input'), true);
                if (isset($json['csrf_token'])) {
                    $token = $json['csrf_token'];
                }
            }
        }

        if (empty($token)) {
            return false;
        }

        if (!isset($_SESSION[self::$sessionKey])) {
            return false;
        }

        $tokenData = $_SESSION[self::$sessionKey];

        if ((time() - $tokenData['time']) >= self::$tokenLifetime) {
            unset($_SESSION[self::$sessionKey]);
            return false;
        }

        return hash_equals($tokenData['token'], $token);
    }

    public static function verify()
    {
        if (!self::validateToken()) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'CSRF token validation failed'
            ]);
            exit();
        }
    }

    public static function getTokenInput()
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    public static function getTokenMeta()
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    public static function refreshToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::$sessionKey]);
        return self::generateToken();
    }
}
