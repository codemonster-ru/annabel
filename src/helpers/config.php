<?php

use Codemonster\Config\Config;

if (!function_exists('config')) {
    function config(string|array|null $key = null, mixed $default = null): mixed
    {
        if (!app()->has(Config::class)) {
            return $default;
        }

        $config = app(Config::class);

        if ($key === null) {
            return $config->all();
        }

        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $config->set($name, $value);
            }

            return true;
        }

        return $config->get($key, $default);
    }
}
