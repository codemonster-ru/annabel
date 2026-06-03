<?php

use Codemonster\Security\Csrf\CsrfTokenManager;

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (function_exists('app')) {
            try {
                $manager = app(CsrfTokenManager::class);

                if ($manager instanceof CsrfTokenManager) {
                    return $manager->token();
                }
            } catch (Throwable $e) {
            }
        }

        return (new CsrfTokenManager())->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $inputKey = '_token';

        if (function_exists('config')) {
            $configured = config('security.csrf.input_key', $inputKey);

            if (is_string($configured) && $configured !== '') {
                $inputKey = $configured;
            }
        }

        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . $name . '" value="' . $token . '">';
    }
}
