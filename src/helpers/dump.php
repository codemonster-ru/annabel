<?php

use Codemonster\Dumper\Dumper;

if (!function_exists('dump')) {
    function dump(...$vars)
    {
        $dumper = null;

        if (function_exists('app') && app()->has(Dumper::class)) {
            $dumper = app(Dumper::class);
        } elseif (class_exists(Dumper::class)) {
            $dumper = new Dumper();
        }

        foreach ($vars as $var) {
            if ($dumper) {
                $dumper::dump($var);
            } else {
                if (PHP_SAPI === 'cli') {
                    var_dump($var);
                } else {
                    echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
                }
            }
        }

        return count($vars) === 1 ? $vars[0] : $vars;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars): never
    {
        dump(...$vars);

        exit(1);
    }
}
