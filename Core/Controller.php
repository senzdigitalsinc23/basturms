<?php

namespace App\Core;

class Controller
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Render a PHP view file and pass data to it.
     *
     * @param string $viewPath Relative to app/Views (e.g. 'users/show')
     * @param array $data Variables to extract in view
     * @return string Rendered HTML
     */
    protected function render(string $viewPath, array $data = []): string
    {
        $viewFile = dirname(__DIR__, 2) . '/app/views/' . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View file {$viewFile} not found.");
        }

        // Extract data variables into scope
        extract($data, EXTR_SKIP);

        // Start output buffering
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }

    /**
     * Generate URL by route name
     */
    protected function route(string $name, array $params = []): string
    {
        return $this->router->generateUri($name, $params) ?? '#';
    }

    /**
     * Simple helper to redirect
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
