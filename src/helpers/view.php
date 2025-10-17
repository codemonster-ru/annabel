<?php

use Codemonster\View\View;
use Codemonster\Annabel\Http\Response;

if (!function_exists('view')) {
    /**
     * @template T of \Codemonster\View\View
     * @param string|null $template
     * @param array $data
     * @return ($template is null ? \Codemonster\View\View : \Codemonster\Annabel\Http\Response)
     */
    function view(?string $template = null, array $data = []): Response|View
    {
        if (function_exists('app') && app()->has(View::class)) {
            $view = app(View::class);

            if ($template === null) {
                return $view;
            }

            return new Response($view->render($template, $data));
        }

        if (class_exists(View::class)) {
            $view = new View();

            if ($template === null) {
                return $view;
            }

            return new Response($view->render($template, $data));
        }

        throw new RuntimeException('View service is not available in the current context.');
    }
}

if (!function_exists('render')) {
    function render(string $template, array $data = []): string
    {
        return view()->render($template, $data);
    }
}
