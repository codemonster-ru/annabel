<?php

if (!function_exists('dd')) {
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }

        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        flush();

        die(1);
    }
}
