<?php

namespace Codemonster\Auth\Tests;

use Codemonster\Auth\Guards\SessionGuard;
use Codemonster\Auth\Hashing\NativePasswordHasher;
use Codemonster\Auth\Providers\ArrayUserProvider;
use Codemonster\Session\Handlers\ArraySessionHandler;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;

class SessionGuardTest extends TestCase
{
    public function test_attempt_logs_user_in(): void
    {
        $hasher = new NativePasswordHasher();
        $user = new TestUser(1, 'admin@example.com', $hasher->make('secret'));
        $guard = $this->guard([$user], $hasher);

        $this->assertTrue($guard->attempt([
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]));
        $this->assertTrue($guard->check());
        $this->assertSame(1, $guard->id());
        $this->assertSame($user, $guard->user());
    }

    public function test_attempt_rejects_invalid_password(): void
    {
        $hasher = new NativePasswordHasher();
        $user = new TestUser(1, 'admin@example.com', $hasher->make('secret'));
        $guard = $this->guard([$user], $hasher);

        $this->assertFalse($guard->attempt([
            'email' => 'admin@example.com',
            'password' => 'wrong',
        ]));
        $this->assertTrue($guard->guest());
    }

    public function test_user_is_restored_from_session(): void
    {
        $hasher = new NativePasswordHasher();
        $user = new TestUser(1, 'admin@example.com', $hasher->make('secret'));
        $session = $this->session();
        $session->put('auth.user_id', 1);

        $guard = new SessionGuard(new ArrayUserProvider([$user], $hasher), $session);

        $this->assertSame($user, $guard->user());
    }

    public function test_logout_forgets_user(): void
    {
        $hasher = new NativePasswordHasher();
        $user = new TestUser(1, 'admin@example.com', $hasher->make('secret'));
        $session = $this->session();
        $guard = new SessionGuard(new ArrayUserProvider([$user], $hasher), $session);

        $guard->login($user, regenerateSession: false);
        $guard->logout(invalidateSession: false);

        $this->assertNull($guard->user());
        $this->assertFalse($session->has('auth.user_id'));
    }

    public function test_logout_invalidates_session_by_default(): void
    {
        $hasher = new NativePasswordHasher();
        $user = new TestUser(1, 'admin@example.com', $hasher->make('secret'));
        $session = $this->session();
        $guard = new SessionGuard(new ArrayUserProvider([$user], $hasher), $session);

        $guard->login($user, regenerateSession: false);
        $session->put('cart_id', 'cart-1');

        $guard->logout();

        $this->assertNull($guard->user());
        $this->assertSame([], $session->all());
    }

    /** @param list<TestUser> $users */
    private function guard(array $users, NativePasswordHasher $hasher): SessionGuard
    {
        return new SessionGuard(new ArrayUserProvider($users, $hasher), $this->session());
    }

    private function session(): Store
    {
        $session = new Store(new ArraySessionHandler());
        $session->start();

        return $session;
    }
}
