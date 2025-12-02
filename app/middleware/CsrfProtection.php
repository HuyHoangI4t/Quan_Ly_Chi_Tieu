<?php
namespace App\Middleware;

/**
 * CSRF Protection Middleware
 * Provides Cross-Site Request Forgery protection for forms and AJAX requests
 */
class CsrfProtection
{
    private static $sessionKey = 'csrf_token';
    private static $tokenLifetime = 3600; // 1 hour in seconds

    /**
     * Generate a new CSRF token
     * @return string Generated token
     */
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

    /**
     * Get current CSRF token, generate if not exists
     * @return string Current token
     */
    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if token exists and is not expired
        if (isset($_SESSION[self::$sessionKey])) {
            $tokenData = $_SESSION[self::$sessionKey];
            if ((time() - $tokenData['time']) < self::$tokenLifetime) {
                return $tokenData['token'];
            }
        }

        // Generate new token if not exists or expired
        return self::generateToken();
    }

    /**
     * Validate CSRF token from request
     * @param string|null $token Token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get token from various sources
        if ($token === null) {
            // Check POST data
            if (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            }
            // Check headers (for AJAX requests)
            elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
            // Check JSON body
            elseif ($_SERVER['CONTENT_TYPE'] === 'application/json' || 
                     strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                $json = json_decode(file_get_contents('php://input'), true);
                if (isset($json['csrf_token'])) {
                    $token = $json['csrf_token'];
                }
            }
        }

        // No token provided
        if (empty($token)) {
            return false;
        }

        // No token in session
        if (!isset($_SESSION[self::$sessionKey])) {
            return false;
        }

        $tokenData = $_SESSION[self::$sessionKey];

        // Check if token expired
        if ((time() - $tokenData['time']) >= self::$tokenLifetime) {
            unset($_SESSION[self::$sessionKey]);
            return false;
        }

        // Validate token using timing-safe comparison
        return hash_equals($tokenData['token'], $token);
    }

    /**
     * Verify CSRF token and throw exception if invalid
     * @throws \Exception If token is invalid
     */
    public static function verify()
    {
        if (!self::validateToken()) {
            http_response_code(403);
            throw new \Exception('CSRF token validation failed');
        }
    }

    /**
     * Get HTML input field with CSRF token
     * @return string HTML input field
     */
    public static function getTokenField()
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get meta tag with CSRF token for AJAX requests
     * @return string HTML meta tag
     */
    public static function getTokenMeta()
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    /**
     * Regenerate CSRF token (useful after login/logout)
     */
    public static function regenerateToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$sessionKey]);
        return self::generateToken();
    }

    /**
     * Middleware to protect routes
     * @param callable $next Next middleware/controller
     * @return mixed
     */
    public static function protect($next)
    {
        // Skip CSRF check for GET, HEAD, OPTIONS requests
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return $next();
        }

        // Verify CSRF token
        try {
            self::verify();
            return $next();
        } catch (\Exception $e) {
            // Return JSON error for AJAX requests
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Token bảo mật không hợp lệ. Vui lòng tải lại trang.',
                    'data' => null
                ]);
                exit;
            }
            
            // Return error page for form submissions
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}
