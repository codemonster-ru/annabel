<?php

namespace Codemonster\Auth\Tests;

use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Guards\SessionGuard;
use Codemonster\Auth\Hashing\NativePasswordHasher;
use Codemonster\Auth\Middleware\Authenticate;
use Codemonster\Auth\Providers\ArrayUserProvider;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Session\Handlers\ArraySessionHandler;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;

class AuthenticateTest extends TestCase
{
    public function test_allows_authenticated_request(): void
    {
        $hasher = new NativePasswordHasher();
        $user = new TestUser(1, 'admin@example.com', $hasher->make('secret'));
        $guard = $this->guard($user, $hasher);
        $guard->login($user, regenerateSession: false);

        $middleware = new Authenticate($guard);
        $response = $middleware->handle(new Request('GET', '/admin'), fn () => new Response('OK'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('OK', $response->getContent());
    }

    public function test_returns_json_401_for_guests(): void
    {
        $middleware = new Authenticate($this->guestGuard());
        $response = $middleware->handle(new Request('GET', '/admin'), fn () => new Response('OK'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['message' => 'Unauthenticated.'], json_decode($response->getContent(), true));
    }

    public function test_redirects_web_guests_when_redirect_is_configured(): void
    {
        $middleware = new Authenticate($this->guestGuard(), '/login');
        $response = $middleware->handle(new Request('GET', '/admin'), fn () => new Response('OK'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    private function guard(AuthenticatableInterface $user, NativePasswordHasher $hasher): SessionGuard
    {
        $session = new Store(new ArraySessionHandler());
        $session->start();

        return new SessionGuard(new ArrayUserProvider([$user], $hasher), $session);
    }

    private function guestGuard(): SessionGuard
    {
        $session = new Store(new ArraySessionHandler());
        $session->start();

        return new SessionGuard(new ArrayUserProvider([], new NativePasswordHasher()), $session);
    }
}
