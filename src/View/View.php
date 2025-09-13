<?php

namespace Annabel\View;

class View
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/');
    }

    public function render(string $template, array $data = []): string
    {
        $file = $this->path . '/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View file not found: {$file}");
        }

        extract($data, EXTR_SKIP);

        ob_start();

        require $file;

        return ob_get_clean();
    }
}