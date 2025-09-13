<?php

namespace Annabel\View;

class View
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function render(string $template, array $data = []): string
    {
        $path = "$this->basePath/$template.php";

        if (!file_exists($path)) {
            throw new \RuntimeException("View [{$template}] not found in {$this->basePath}");
        }

        extract($data, EXTR_SKIP);

        ob_start();

        include $path;

        return ob_get_clean();
    }
}
