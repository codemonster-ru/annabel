<?php

use Codemonster\View\View;
use Codemonster\Http\Response;

if (!function_exists('view')) {
    /** @param array<string, mixed> $data */
    function view(?string $template = null, array $data = []): View|Response
    {
        $view = app('view');
        if (!$view instanceof View) {
            throw new RuntimeException('View service is not available.');
        }

        if ($template === null) {
            return $view;
        }

        return new Response($view->render($template, $data));
    }
}

if (!function_exists('render')) {
    /** @param array<string, mixed> $data */
    function render(string $template, array $data = []): string
    {
        $view = app('view');
        if (!$view instanceof View) {
            throw new RuntimeException('View service is not available.');
        }

        return $view->render($template, $data);
    }
}
