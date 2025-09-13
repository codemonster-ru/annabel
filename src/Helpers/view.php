<?php

use Annabel\View\View;
use Annabel\Http\Response;

if (!function_exists('view')) {
    function view(string $name, array $data = []): Response
    {
        $basePath = base_path('resources/views');

        $view = new View($basePath, $name, $data);

        return new Response($view->render());
    }
}