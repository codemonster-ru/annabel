<?php

use Codemonster\Dumper\Dumper;

if (!function_exists('dump')) {
    /** @param mixed ...$vars */
    function dump(...$vars): mixed
    {
        foreach ($vars as $var) {
            Dumper::dump($var);
        }

        return count($vars) === 1 ? $vars[0] : $vars;
    }
}

if (!function_exists('dd')) {
    /** @param mixed ...$vars */
    function dd(...$vars): never
    {
        dump(...$vars);

        exit(0);
    }
}
