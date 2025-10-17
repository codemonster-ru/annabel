<?php

use Codemonster\Env\Env;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        if (function_exists('app') && app()->has(Env::class)) {
            $env = app(Env::class);

            return $env->get($key, $default);
        }

        if (class_exists(Env::class) && method_exists(Env::class, 'get')) {
            return Env::get($key, $default);
        }

        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
