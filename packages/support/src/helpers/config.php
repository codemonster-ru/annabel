<?php

use Codemonster\Config\Config;

if (!function_exists('config')) {
    /** @param string|array<string, mixed>|null $key */
    function config(string|array|null $key = null, mixed $default = null): mixed
    {
        $config = app('config');
        if (!$config instanceof Config) {
            throw new RuntimeException('Config service is not available.');
        }

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
