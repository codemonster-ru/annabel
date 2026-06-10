<?php

use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\AuthorizerInterface;
use Codemonster\Auth\Contracts\GuardInterface;

if (!function_exists('auth')) {
    function auth(): GuardInterface
    {
        $guard = app('auth');

        if (!$guard instanceof GuardInterface) {
            throw new RuntimeException('Auth service is not available.');
        }

        return $guard;
    }
}

if (!function_exists('user')) {
    function user(): ?AuthenticatableInterface
    {
        return auth()->user();
    }
}

if (!function_exists('gate')) {
    function gate(): AuthorizerInterface
    {
        $gate = app('gate');

        if (!$gate instanceof AuthorizerInterface) {
            throw new RuntimeException('Authorization service is not available.');
        }

        return $gate;
    }
}

if (!function_exists('can')) {
    function can(string $ability, mixed ...$arguments): bool
    {
        return gate()->allows($ability, ...$arguments);
    }
}

if (!function_exists('cannot')) {
    function cannot(string $ability, mixed ...$arguments): bool
    {
        return gate()->denies($ability, ...$arguments);
    }
}

if (!function_exists('authorize')) {
    function authorize(string $ability, mixed ...$arguments): void
    {
        gate()->authorize($ability, ...$arguments);
    }
}
