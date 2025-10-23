<?php

use Codemonster\Http\Response;

if (!function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        if (function_exists('app') && app()->has(Response::class)) {
            $res = app(Response::class);

            if ($content !== '') {
                $res->setContent($content);
            }

            $res->setStatusCode($status)
                ->setHeaders($headers);

            return $res;
        }

        return new Response($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return Response::json($data, $status, $headers);
    }
}
