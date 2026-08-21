<?php

class Router
{
    private array $routes = [];
    private array $groups = [];
    private string $currentGroup = '';
    private string $currentPrefix = '';

    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function any(string $path, callable|array $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    public function group(string $prefix, callable $callback): self
    {
        $previousPrefix = $this->currentPrefix;
        $this->currentPrefix = $previousPrefix . '/' . trim($prefix, '/');

        $callback($this);

        $this->currentPrefix = $previousPrefix;
        return $this;
    }

    public function middleware(string $name): self
    {
        if (!isset($this->groups[$this->currentPrefix])) {
            $this->groups[$this->currentPrefix] = [];
        }
        $this->groups[$this->currentPrefix][] = $name;
        return $this;
    }

    private function addRoute(string $method, string $path, callable|array $handler): self
    {
        $fullPath = $this->currentPrefix . '/' . trim($path, '/');
        $fullPath = $fullPath === '' ? '/' : $fullPath;

        $middleware = $this->groups[$this->currentPrefix] ?? [];

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware'  => $middleware,
        ];

        return $this;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        $route = $this->matchRoute($method, $uri);

        if ($route === null) {
            if ($uri !== '/' && $this->matchRoute($method, $uri . '/') !== null) {
                $this->redirect($uri . '/');
                return;
            }
            $this->notFound();
            return;
        }

        if (!empty($route['middleware'])) {
            foreach ($route['middleware'] as $mw) {
                if (is_callable($mw)) {
                    $result = $mw();
                } elseif (method_exists('Middleware', $mw)) {
                    $result = Middleware::$mw();
                } else {
                    $result = true;
                }
                if ($result === false) {
                    return;
                }
            }
        }

        if ($method !== 'GET' && $method !== 'HEAD') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Auth::verifyCsrfToken($token)) {
                http_response_code(403);
                echo 'CSRF token mismatch.';
                return;
            }
        }

        $params = $route['params'] ?? [];
        $handler = $route['handler'];

        if (is_array($handler)) {
            [$controllerName, $action] = $handler;
            $controller = new $controllerName();
            call_user_func_array([$controller, $action], $params);
        } else {
            call_user_func_array($handler, $params);
        }
    }

    private function matchRoute(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->routeToRegex($route['path']);

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return array_merge($route, ['params' => $params]);
            }
        }

        return null;
    }

    private function routeToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function notFound(): void
    {
        if (file_exists(BASE_PATH . '/includes/controllers/HomeController.php')) {
            $controller = new HomeController();
            $controller->index();
        } else {
            http_response_code(404);
            echo '<h1>404 - Page Not Found</h1>';
        }
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url, true, 301);
        exit;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
