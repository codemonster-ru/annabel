<?php

namespace Codemonster\Auth\Contracts;

interface AuthorizerInterface
{
    /**
     * @param callable(AuthenticatableInterface|null, mixed ...): bool $callback
     */
    public function define(string $ability, callable $callback): void;

    /**
     * @param class-string $class
     * @param class-string|object $policy
     */
    public function policy(string $class, string|object $policy): void;

    public function allows(string $ability, mixed ...$arguments): bool;

    public function denies(string $ability, mixed ...$arguments): bool;

    public function authorize(string $ability, mixed ...$arguments): void;
}
