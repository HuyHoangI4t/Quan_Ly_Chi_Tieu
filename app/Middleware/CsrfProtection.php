<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\SessionManager;

final class CsrfProtection
{
    private static string $sessionKey = 'csrf_token';
    private static int $tokenLifetime = 3600; // seconds

    public static function generateToken(): string
    {
        $sm = new SessionManager();
        $token = bin2hex(random_bytes(32));
        $sm->set(self::$sessionKey, [
            'token' => $token,
            'time' => time(),
        ]);

        return $token;
    }

    public static function getToken(): string
    {
        $sm = new SessionManager();
        $tokenData = $sm->get(self::$sessionKey);

        if (!empty($tokenData)) {
            if ((time() - ($tokenData['time'] ?? 0)) < self::$tokenLifetime && !empty($tokenData['token'])) {
                return (string) $tokenData['token'];
            }
        }

        return self::generateToken();
    }

    public static function validateToken(?string $token = null): bool
    {
        $request = new Request();

        if ($token === null) {
            $token = $request->post('csrf_token');
            if (!$token) {
                // Check headers
                $token = $request->header('X-CSRF-TOKEN');
            }
            if (!$token) {
                // Check JSON body
                $token = $request->json('csrf_token');
            }
        }

        if (!$token) {
            return false;
        }

        $sm = new SessionManager();
        $tokenData = $sm->get(self::$sessionKey);
        if (empty($tokenData) || empty($tokenData['token'])) {
            return false;
        }

        if ((time() - ($tokenData['time'] ?? 0)) >= self::$tokenLifetime) {
            $sm->remove(self::$sessionKey);
            return false;
        }

        return hash_equals((string) $tokenData['token'], (string) $token);
    }

    public static function verify(): void
    {
        if (!self::validateToken()) {
            // Log CSRF failures for debugging
            try {
                $req = new Request();
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $payload = json_encode([ 'post'=>$_POST, 'get'=>$_GET ]);
                $msg = sprintf("[%s] CSRF failed: ip=%s uri=%s payload=%s\n", date('Y-m-d H:i:s'), $ip, $uri, $payload);
                @file_put_contents(__DIR__ . '/../../storage/logs/csrf_fail.log', $msg, FILE_APPEND);
            } catch (\Throwable $t) {}

            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'CSRF token validation failed',
            ]);
            exit;
        }
    }

    public static function getTokenInput(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function getTokenMeta(): string
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function refreshToken(): string
    {
        $sm = new SessionManager();
        $sm->remove(self::$sessionKey);
        return self::generateToken();
    }
}
