<?php

if (!function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        $request = app('request');

        if ($key === null) {
            return $request;
        }

        return $request->input($key, $default) ?? $request->query($key, $default);
    }
}
