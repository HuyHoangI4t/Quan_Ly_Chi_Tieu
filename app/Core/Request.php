<?php
namespace App\Core;

class Request
{
    private array $routeParams = [];

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        // Remove base URL if present
        if (defined('BASE_URL')) {
            $baseUrl = parse_url(BASE_URL, PHP_URL_PATH);
            if ($baseUrl && strpos($path, $baseUrl) === 0) {
                $path = substr($path, strlen($baseUrl));
            }
        }

        return $path ?: '/';
    }

    /**
     * Get a GET parameter
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get a value from session
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function session(string $key, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSession(string $key, $value): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$key] = $value;
    }

    /**
     * Unset a session key
     * @param string $key
     * @return void
     */
    public function unsetSession(string $key): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the session
     * @return void
     */
    public function destroySession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }

    /**
     * Get a POST parameter
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get a request input (GET/POST)
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Parse JSON body
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function json(?string $key = null, $default = null)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if ($key) {
            return $data[$key] ?? $default;
        }
        return $data;
    }

    public function all(): array
    {
        $data = array_merge($_GET, $_POST);

        // If request uses JSON body (Content-Type: application/json), merge it too
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if ($contentType && stripos($contentType, 'application/json') !== false) {
            $raw = @file_get_contents('php://input');
            if ($raw) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $data = array_merge($data, $json);
                }
            }
        }

        return $data;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get a route parameter
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getRouteParam(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Get a header value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }

    public function ip(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
