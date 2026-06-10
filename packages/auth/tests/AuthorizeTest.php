<?php

namespace Codemonster\Auth\Tests;

use Codemonster\Auth\Contracts\AuthorizerInterface;
use Codemonster\Auth\Middleware\Authorize;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use PHPUnit\Framework\TestCase;

class AuthorizeTest extends TestCase
{
    public function test_allows_authorized_requests(): void
    {
        $middleware = new Authorize(new TestAuthorizer(true));
        $request = (new Request('GET', '/posts/42'))->withAttribute('route.parameters', ['post' => '42']);

        $response = $middleware->handle(
            $request,
            fn () => new Response('ok'),
            'posts.update,post',
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('ok', $response->getContent());
    }

    public function test_denies_forbidden_requests(): void
    {
        $middleware = new Authorize(new TestAuthorizer(false));
        $request = (new Request('GET', '/posts/42', headers: ['Accept' => 'application/json']))
            ->withAttribute('route.parameters', ['post' => '42']);

        $response = $middleware->handle(
            $request,
            fn () => new Response('ok'),
            'posts.update,post',
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame(['message' => 'Forbidden.'], json_decode($response->getContent(), true));
    }

    public function test_requires_route_parameters(): void
    {
        $middleware = new Authorize(new TestAuthorizer(true));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('post');

        $middleware->handle(new Request('GET', '/posts/42'), fn () => new Response('ok'), 'posts.update,post');
    }
}

class TestAuthorizer implements AuthorizerInterface
{
    public function __construct(private bool $allowed)
    {
    }

    public function define(string $ability, callable $callback): void
    {
    }

    public function policy(string $class, string|object $policy): void
    {
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        return $this->allowed;
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): void
    {
    }
}
