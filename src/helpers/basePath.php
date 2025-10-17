<?php

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = app()->getBasePath();

        if ($path === '') {
            return $base;
        }

        return rtrim($base, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('basePath')) {
    function basePath(string $path = ''): string
    {
        return base_path($path);
    }
}
