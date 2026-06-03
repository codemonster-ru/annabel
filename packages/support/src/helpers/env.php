<?php

use Codemonster\Env\Env;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null, bool $cast = false): mixed
    {
        return Env::getCast($key, $default, $cast);
    }
}
