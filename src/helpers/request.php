<?php

use Codemonster\Annabel\Http\Request;

if (!function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        if (function_exists('app') && app()->has(Request::class)) {
            $req = app(Request::class);

            if ($key === null) {
                return $req;
            }

            return $req->input($key, $default)
                ?? $req->query($key, $default);
        }

        if (class_exists(Request::class)) {
            $req = Request::capture();

            if ($key === null) {
                return $req;
            }

            return $req->input($key, $default)
                ?? $req->query($key, $default);
        }

        return $default;
    }
}
