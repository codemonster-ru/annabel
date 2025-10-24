<?php

use Codemonster\Session\Session;

if (!function_exists('session')) {
    function session(?string $key = null, mixed $value = null): mixed
    {
        if ($key === null) {
            return Session::store();
        }

        if ($value === null) {
            return Session::get($key);
        }

        Session::put($key, $value);

        return Session::store();
    }
}
