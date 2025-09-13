<?php

use Annabel\Application;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = Application::getInstance()->getBasePath();

        return $path ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }
}
