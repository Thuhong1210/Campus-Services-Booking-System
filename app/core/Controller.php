<?php

declare(strict_types=1);

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $contentFile = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($contentFile)) {
            throw new RuntimeException("View not found: $view");
        }
        $layoutFile = VIEW_PATH . '/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            require $contentFile;
            return;
        }
        ob_start();
        require $contentFile;
        $content = ob_get_clean();
        require $layoutFile;
    }

    protected function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function post(): array
    {
        return $_POST;
    }

    protected function get(): array
    {
        return $_GET;
    }

    protected function verifyCsrf(): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            Flash::error('Invalid security token. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
        }
    }
}
