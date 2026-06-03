<?php

use Codemonster\View\View;
use Codemonster\Http\Response;

if (!function_exists('view')) {
    function view(?string $template = null, array $data = []): View|Response
    {
        $view = app('view');

        if ($template === null) {
            return $view;
        }

        return new Response($view->render($template, $data));
    }
}

if (!function_exists('render')) {
    function render(string $template, array $data = []): string
    {
        return app('view')->render($template, $data);
    }
}
