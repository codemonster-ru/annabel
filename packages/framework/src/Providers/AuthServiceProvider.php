<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Auth\Authorization\Gate;
use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\AuthorizerInterface;
use Codemonster\Auth\Contracts\GuardInterface;
use Codemonster\Auth\Contracts\PasswordHasherInterface;
use Codemonster\Auth\Contracts\UserProviderInterface;
use Codemonster\Auth\Database\DatabaseUserProvider;
use Codemonster\Auth\Guards\SessionGuard;
use Codemonster\Auth\Hashing\NativePasswordHasher;
use Codemonster\Auth\Middleware\Authenticate;
use Codemonster\Auth\Middleware\Authorize;
use Codemonster\Auth\Providers\ArrayUserProvider;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Session\Store;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/auth.php' => $this->app()->getBasePath() . '/config/auth.php',
        ], ['config', 'auth']);

        $this->app()->singleton(PasswordHasherInterface::class, fn (Container $app): PasswordHasherInterface => $this->hasher($app));
        $this->app()->singleton('auth.hasher', fn (Container $app): PasswordHasherInterface => $app->make(PasswordHasherInterface::class));

        $this->app()->singleton(UserProviderInterface::class, fn (Container $app): UserProviderInterface => $this->userProvider($app));
        $this->app()->singleton('auth.provider', fn (Container $app): UserProviderInterface => $app->make(UserProviderInterface::class));

        $this->app()->singleton(SessionGuard::class, fn (Container $app): SessionGuard => new SessionGuard(
            $app->make(UserProviderInterface::class),
            $this->session($app),
            $this->stringConfig('auth.session_key', 'auth.user_id'),
        ));

        $this->app()->singleton(GuardInterface::class, fn (Container $app): GuardInterface => $app->make(SessionGuard::class));
        $this->app()->singleton('auth', fn (Container $app): GuardInterface => $app->make(GuardInterface::class));

        $this->app()->singleton(AuthorizerInterface::class, fn (Container $app): AuthorizerInterface => $this->gate($app));
        $this->app()->singleton('gate', fn (Container $app): AuthorizerInterface => $app->make(AuthorizerInterface::class));

        $this->app()->bind(Authenticate::class, fn (Container $app): Authenticate => new Authenticate(
            $app->make(GuardInterface::class),
            $this->nullableStringConfig('auth.redirect_to'),
        ));

        $this->app()->bind(Authorize::class, fn (Container $app): Authorize => new Authorize(
            $app->make(AuthorizerInterface::class),
        ));
    }

    public function boot(): void
    {
        $kernel = $this->app()->make(Kernel::class);

        $kernel->aliasMiddleware('auth', Authenticate::class);
        $kernel->aliasMiddleware('can', Authorize::class);
    }

    private function hasher(Container $app): PasswordHasherInterface
    {
        $configured = config('auth.hasher');

        if ($configured instanceof PasswordHasherInterface) {
            return $configured;
        }

        if (is_string($configured) && $configured !== '') {
            $hasher = $app->make($configured);

            if (!$hasher instanceof PasswordHasherInterface) {
                throw new \RuntimeException("Configured auth hasher [{$configured}] must implement " . PasswordHasherInterface::class . '.');
            }

            return $hasher;
        }

        return new NativePasswordHasher();
    }

    private function userProvider(Container $app): UserProviderInterface
    {
        $configured = config('auth.provider');

        if ($configured instanceof UserProviderInterface) {
            return $configured;
        }

        if ($configured === 'database') {
            return new DatabaseUserProvider(
                $app->make(ConnectionInterface::class),
                $app->make(PasswordHasherInterface::class),
                $this->stringConfig('auth.database.table', 'users'),
                $this->stringConfig('auth.database.identifier_column', 'id'),
                $this->stringConfig('auth.database.password_column', 'password'),
                $this->stringConfig('auth.credential_key', 'email'),
            );
        }

        if (is_string($configured) && $configured !== '') {
            $provider = $app->make($configured);

            if (!$provider instanceof UserProviderInterface) {
                throw new \RuntimeException("Configured auth provider [{$configured}] must implement " . UserProviderInterface::class . '.');
            }

            return $provider;
        }

        return new ArrayUserProvider(
            $this->usersConfig(),
            $app->make(PasswordHasherInterface::class),
            $this->stringConfig('auth.credential_key', 'email'),
        );
    }

    private function gate(Container $app): Gate
    {
        $gate = new Gate($app->make(GuardInterface::class));

        foreach ($this->callableMapConfig('auth.abilities') as $ability => $callback) {
            $gate->define($ability, $callback);
        }

        foreach ($this->policyMapConfig('auth.policies') as $class => $policy) {
            $gate->policy($class, $policy);
        }

        return $gate;
    }

    private function session(Container $app): Store
    {
        $session = $app->make('session');

        if (!$session instanceof Store) {
            throw new \RuntimeException('Auth requires the session service to be a ' . Store::class . ' instance.');
        }

        return $session;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function nullableStringConfig(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array<string, callable>
     */
    private function callableMapConfig(string $key): array
    {
        $value = config($key, []);

        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $name => $callback) {
            if (is_string($name) && is_callable($callback)) {
                $items[$name] = $callback;
            }
        }

        return $items;
    }

    /**
     * @return array<class-string, class-string|object>
     */
    private function policyMapConfig(string $key): array
    {
        $value = config($key, []);

        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $class => $policy) {
            if (!is_string($class) || !class_exists($class)) {
                continue;
            }

            if (is_object($policy) || (is_string($policy) && class_exists($policy))) {
                $items[$class] = $policy;
            }
        }

        return $items;
    }

    /** @return list<AuthenticatableInterface> */
    private function usersConfig(): array
    {
        $value = config('auth.users', []);

        if (!is_array($value)) {
            return [];
        }

        $users = [];
        foreach ($value as $user) {
            if ($user instanceof AuthenticatableInterface) {
                $users[] = $user;
            }
        }

        return $users;
    }
}
