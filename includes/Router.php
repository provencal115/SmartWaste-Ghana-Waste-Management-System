<?php
/**
 * Simple URL Router
 */
class Router
{
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = trim($_GET['url'] ?? '', '/');
        if ($url === '') $url = 'home';

        $handler = $this->routes[$method][$url] ?? null;

        if (!$handler) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        [$controllerName, $action] = explode('@', $handler);
        $controllerFile = __DIR__ . "/../controllers/{$controllerName}.php";

        if (!file_exists($controllerFile)) {
            http_response_code(500);
            error_log("SmartWaste: controller file missing — {$controllerName}");
            require __DIR__ . '/../views/errors/500.php';
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            http_response_code(500);
            error_log("SmartWaste: controller class missing — {$controllerName}");
            require __DIR__ . '/../views/errors/500.php';
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $controller->$action();
    }
}
