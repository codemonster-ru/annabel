<?php

if (!function_exists('session')) {
    function session(?string $key = null, mixed $value = null): mixed
    {
        $session = app('session');

        if ($key === null) {
            return $session;
        }

        if (func_num_args() === 1) {
            return $session->get($key);
        }

        $session->put($key, $value);

        return $session;
    }
}
