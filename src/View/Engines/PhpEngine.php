<?php

namespace Annabel\View\Engines;

class PhpEngine
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function render(string $view, array $data = []): string
    {
        $path = "$this->basePath/$view.php";

        if (!file_exists($path)) {
            throw new \RuntimeException("View [{$view}] not found in {$this->basePath}");
        }

        extract($data, EXTR_SKIP);

        ob_start();

        include $path;

        return ob_get_clean();
    }
}