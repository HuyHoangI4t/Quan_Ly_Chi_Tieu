<?php
namespace App\Core;

use App\Middleware\MiddlewareInterface;
use InvalidArgumentException;

class Router
{
    private Request $request;
    private Response $response;
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $routes = [];
    private array $middlewareAliases = [
        'auth' => \App\Middleware\AuthMiddleware::class,
        'guest' => \App\Middleware\GuestMiddleware::class,
    ];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute(['GET'], $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute(['POST'], $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute(['PUT'], $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute(['DELETE'], $path, $handler, $middleware);
    }

    public function match(array $methods, string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute($methods, $path, $handler, $middleware);
    }

    public function dispatch(): bool
    {
        $method = $this->request->method();
        $path = rtrim($this->request->path(), '/') ?: '/';

        $candidates = $this->routes[$method] ?? [];
        $candidates = array_merge($candidates, $this->routes['ANY'] ?? []);

        foreach ($candidates as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = [];
                foreach ($route['params'] as $name) {
                    $params[$name] = isset($matches[$name]) ? $this->sanitizeParam($matches[$name]) : null;
                }
                $this->request->setRouteParams($params);
                $this->executeRoute($route, $params);
                return true;
            }
        }

        return false;
    }

    private function addRoute(array $methods, string $path, $handler, array $middleware = []): self
    {
        [$pattern, $paramNames] = $this->compilePath($path);

        foreach ($methods as $method) {
            $method = strtoupper($method);
            $this->routes[$method][] = [
                'path' => $path,
                'pattern' => $pattern,
                'params' => $paramNames,
                'handler' => $handler,
                'middleware' => $middleware,
            ];
        }

        return $this;
    }

    private function compilePath(string $path): array
    {
        $paramNames = [];

        $trimmed = trim($path);
        if ($trimmed === '') {
            $trimmed = '/';
        }

        if ($trimmed === '/') {
            return ['#^/$#', $paramNames];
        }

        $segments = explode('/', trim($trimmed, '/'));
        $regex = '';

        foreach ($segments as $segment) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)(\?)?\}$/', $segment, $matches)) {
                $param = $matches[1];
                $optional = !empty($matches[2]);
                $paramNames[] = $param;
                if ($optional) {
                    $regex .= '(?:/(?P<' . $param . '>[^/]+))?';
                } else {
                    $regex .= '/(?P<' . $param . '>[^/]+)';
                }
                continue;
            }

            $regex .= '/' . preg_quote($segment, '#');
        }

        $regex = '#^' . $regex . '/?$#i';
        return [$regex, $paramNames];
    }

    private function executeRoute(array $route, array $params): void
    {
        $handler = $this->wrapHandler($route['handler'], $route['params'], $params);
        $pipeline = $this->buildMiddlewarePipeline($route['middleware'], $handler);
        $pipeline();
    }

    private function wrapHandler($handler, array $paramOrder, array $params): callable
    {
        $orderedParams = [];
        foreach ($paramOrder as $name) {
            if (!array_key_exists($name, $params) || $params[$name] === null) {
                break;
            }
            $orderedParams[] = $params[$name];
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;
            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf('Controller %s not found', $class));
            }
            if (!method_exists($class, $method)) {
                throw new InvalidArgumentException(sprintf('Method %s::%s not found', $class, $method));
            }

            return function () use ($class, $method, $orderedParams) {
                $controller = new $class($this->request, $this->response);
                return call_user_func_array([$controller, $method], $orderedParams);
            };
        }

        if (is_callable($handler)) {
            return function () use ($handler, $orderedParams) {
                return call_user_func_array($handler, $orderedParams);
            };
        }

        throw new InvalidArgumentException('Invalid route handler specified');
    }

    private function buildMiddlewarePipeline(array $middlewares, callable $handler): callable
    {
        $next = $handler;
        $middlewares = array_reverse($middlewares);

        foreach ($middlewares as $middleware) {
            $instance = $this->resolveMiddleware($middleware);
            $next = function () use ($instance, $next) {
                return $instance->handle($this->request, $this->response, $next);
            };
        }

        return $next;
    }

    private function resolveMiddleware($middleware): MiddlewareInterface
    {
        if (is_string($middleware) && isset($this->middlewareAliases[$middleware])) {
            $middleware = $this->middlewareAliases[$middleware];
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $middleware = new $middleware();
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException('Invalid middleware provided to router');
        }

        return $middleware;
    }

    private function sanitizeParam($value)
    {
        if ($value === null) {
            return null;
        }

        return is_string($value)
            ? filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            : $value;
    }
}
