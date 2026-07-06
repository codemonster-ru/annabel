<?php

declare(strict_types=1);

namespace Codemonster\Auth\Tests;

use Codemonster\Auth\Authorization\AuthorizationException;
use Codemonster\Auth\Authorization\Gate;
use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\GuardInterface;
use PHPUnit\Framework\TestCase;

class GateTest extends TestCase
{
    public function test_defined_abilities_can_authorize_current_user(): void
    {
        $user = new TestUser(1, 'admin@example.com', 'hash');
        $gate = new Gate(new TestGuard($user));

        $gate->define('posts.update', static function (?AuthenticatableInterface $current, mixed ...$posts): bool {
            $post = $posts[0] ?? null;
            self::assertInstanceOf(PostSubject::class, $post);

            return $current?->getAuthIdentifier() === $post->ownerId;
        });

        self::assertTrue($gate->allows('posts.update', new PostSubject(1)));
        self::assertFalse($gate->allows('posts.update', new PostSubject(2)));
        self::assertTrue($gate->denies('posts.delete', new PostSubject(1)));
    }

    public function test_policies_can_authorize_subjects(): void
    {
        $gate = new Gate(new TestGuard(new TestUser(7, 'admin@example.com', 'hash')));
        $gate->policy(PostSubject::class, PostPolicy::class);

        self::assertTrue($gate->allows('update', new PostSubject(7)));
        self::assertFalse($gate->allows('update', new PostSubject(8)));
    }

    public function test_authorize_throws_when_denied(): void
    {
        $gate = new Gate(new TestGuard(null));
        $gate->define('admin', fn (?AuthenticatableInterface $user): bool => $user !== null);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('admin');

        $gate->authorize('admin');
    }
}

class TestGuard implements GuardInterface
{
    public function __construct(private ?AuthenticatableInterface $user)
    {
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function guest(): bool
    {
        return $this->user === null;
    }

    public function user(): ?AuthenticatableInterface
    {
        return $this->user;
    }

    public function id(): int|string|null
    {
        return $this->user?->getAuthIdentifier();
    }

    public function attempt(array $credentials): bool
    {
        return false;
    }

    public function login(AuthenticatableInterface $user, bool $regenerateSession = true): void
    {
        $this->user = $user;
    }

    public function logout(bool $invalidateSession = true): void
    {
        $this->user = null;
    }

    public function validate(array $credentials): bool
    {
        return false;
    }
}

class PostSubject
{
    public function __construct(public int|string $ownerId)
    {
    }
}

class PostPolicy
{
    public function update(?AuthenticatableInterface $user, PostSubject $post): bool
    {
        return $user?->getAuthIdentifier() === $post->ownerId;
    }
}
