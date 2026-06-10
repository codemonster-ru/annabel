<?php

namespace Codemonster\Auth\Authorization;

use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\AuthorizerInterface;
use Codemonster\Auth\Contracts\GuardInterface;

class Gate implements AuthorizerInterface
{
    /**
     * @var array<string, callable(AuthenticatableInterface|null, mixed ...): bool>
     */
    protected array $abilities = [];

    /**
     * @var array<class-string, class-string|object>
     */
    protected array $policies = [];

    public function __construct(protected GuardInterface $guard)
    {
    }

    public function define(string $ability, callable $callback): void
    {
        if ($ability === '') {
            throw new \InvalidArgumentException('Authorization ability name cannot be empty.');
        }

        $this->abilities[$ability] = $callback;
    }

    public function policy(string $class, string|object $policy): void
    {
        if ($class === '' || !class_exists($class)) {
            throw new \InvalidArgumentException("Authorization policy target [{$class}] must be an existing class.");
        }

        if (is_string($policy) && ($policy === '' || !class_exists($policy))) {
            throw new \InvalidArgumentException("Authorization policy [{$policy}] must be an existing class.");
        }

        $this->policies[$class] = $policy;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        $user = $this->guard->user();

        if (isset($this->abilities[$ability])) {
            return (bool) ($this->abilities[$ability])($user, ...$arguments);
        }

        $policy = $this->resolvePolicy($arguments[0] ?? null);

        if ($policy === null || !method_exists($policy, $ability)) {
            return false;
        }

        return (bool) $policy->{$ability}($user, ...$arguments);
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): void
    {
        if ($this->denies($ability, ...$arguments)) {
            throw new AuthorizationException($ability);
        }
    }

    /**
     * @return array<string, callable(AuthenticatableInterface|null, mixed ...): bool>
     */
    public function abilities(): array
    {
        return $this->abilities;
    }

    /**
     * @return array<class-string, class-string|object>
     */
    public function policies(): array
    {
        return $this->policies;
    }

    private function resolvePolicy(mixed $subject): ?object
    {
        if (!is_object($subject)) {
            return null;
        }

        foreach ($this->policies as $class => $policy) {
            if (!$subject instanceof $class) {
                continue;
            }

            return is_string($policy) ? new $policy() : $policy;
        }

        return null;
    }
}
