<?php
/**
 * Base Controller
 */
class Controller
{
    protected array $config;

    public function __construct()
    {
        $this->config = appConfig();
    }

    protected function view(string $view, array $data = [], string $layout = 'dashboard'): void
    {
        sendUtf8HtmlHeaders();
        extract($data);
        $config = $this->config;
        $user = Auth::user();
        $csrf = Csrf::token();
        $flash = getFlash();

        ob_start();
        require __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();

        require __DIR__ . "/../views/layouts/{$layout}.php";
    }

    protected function json(mixed $data, int $code = 200): void
    {
        jsonResponse($data, $code);
    }

    protected function redirect(string $route, array $params = []): void
    {
        redirect($route, $params);
    }

    protected function requireRole(array $roles): array
    {
        return Auth::requireRole($roles);
    }

    protected function validateCsrf(): void
    {
        Csrf::validate();
    }
}
