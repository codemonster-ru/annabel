<?php

use Annabel\Application;
use Annabel\Http\Response;

if (!function_exists('view')) {
    function view(string $template, array $data = []): Response
    {
        return Application::getInstance()->view($template, $data);
    }
}
