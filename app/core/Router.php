<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $page, string $controller, string $action, ?callable $middleware = null): void
    {
        $this->routes[$page] = compact('controller', 'action', 'middleware');
    }

    public function dispatch(string $page): void
    {
        if (!isset($this->routes[$page])) {
            http_response_code(404);
            Flash::error('Page not found.');
            redirect('index.php?page=dashboard');
        }

        $route = $this->routes[$page];
        if ($route['middleware']) {
            ($route['middleware'])();
        }

        $controllerClass = $route['controller'];
        $action = $route['action'];
        if (!class_exists($controllerClass)) {
            throw new RuntimeException("Controller not found: $controllerClass");
        }
        $controller = new $controllerClass();
        if (!method_exists($controller, $action)) {
            throw new RuntimeException("Action not found: $action");
        }
        $controller->$action();
    }
}
