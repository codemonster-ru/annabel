<?php

use Codemonster\Annabel\Assets\Vite;

if (!function_exists('vite')) {
    /**
     * @param string|list<string> $entries
     */
    function vite(string|array $entries): string
    {
        $vite = app(Vite::class);

        if (!$vite instanceof Vite) {
            throw new RuntimeException('Vite service is not registered.');
        }

        return $vite->render($entries);
    }
}
