<?php

use Codemonster\Session\Session;
use Codemonster\Session\Store;

if (!function_exists('old')) {
    function old(?string $key = null, mixed $default = null): mixed
    {
        $session = session();
        if (!$session instanceof Store && !$session instanceof Session) {
            throw new RuntimeException('Session service is not available.');
        }

        $input = $session->get('_old_input', []);

        if (!is_array($input)) {
            return $default;
        }

        if ($key === null) {
            return $input;
        }

        if (str_contains($key, '.')) {
            $value = $input;

            foreach (explode('.', $key) as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return $default;
                }

                $value = $value[$segment];
            }

            return $value;
        }

        return $input[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    /**
     * @return mixed
     */
    function errors(?string $field = null): mixed
    {
        $session = session();
        if (!$session instanceof Store && !$session instanceof Session) {
            throw new RuntimeException('Session service is not available.');
        }

        $errors = $session->get('errors', []);

        if (!is_array($errors)) {
            return $field === null ? [] : null;
        }

        if ($field === null) {
            return $errors;
        }

        return $errors[$field] ?? null;
    }
}
