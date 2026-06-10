<?php

use Codemonster\Http\Request;

if (!function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        $request = app('request');
        if (!$request instanceof Request) {
            throw new RuntimeException('Request service is not available.');
        }

        if ($key === null) {
            return $request;
        }

        return $request->input($key, $default) ?? $request->query($key, $default);
    }
}
