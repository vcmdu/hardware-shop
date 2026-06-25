<?php
namespace App\Core;

class Router {
    private array $routes = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, mixed $callback): void {
        $this->routes['GET'][$path] = $callback;
    }

    public function post(string $path, mixed $callback): void {
        $this->routes['POST'][$path] = $callback;
    }

    public function put(string $path, mixed $callback): void {
        $this->routes['PUT'][$path] = $callback;
    }

    public function delete(string $path, mixed $callback): void {
        $this->routes['DELETE'][$path] = $callback;
    }

    public function resolve(): void {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();

        // 1. Exact match
        $callback = $this->routes[$method][$path] ?? false;

        // 2. Dynamic route parameters match (e.g., /api/products/{id})
        $params = [];
        if ($callback === false && isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $routeCallback) {
                // Convert {param} to regex capture group
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
                $pattern = '#^' . $pattern . '$#';
                if (preg_match($pattern, $path, $matches)) {
                    $callback = $routeCallback;
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    break;
                }
            }
        }

        if ($callback === false) {
            $this->response->setStatusCode(404);
            if ($this->request->isAjax()) {
                $this->response->json(['success' => false, 'message' => 'Route not found'], 404);
            } else {
                echo "<h1>404 Not Found</h1><p>The requested route <b>" . htmlspecialchars($path) . "</b> was not found on this server.</p>";
                exit;
            }
        }

        // Execute callback
        if (is_callable($callback)) {
            call_user_func_array($callback, [$this->request, $this->response, $params]);
            return;
        }

        if (is_array($callback)) {
            $controllerClass = $callback[0];
            $action = $callback[1];
            $controller = new $controllerClass();
            call_user_func_array([$controller, $action], [$this->request, $this->response, $params]);
            return;
        }
    }
}
