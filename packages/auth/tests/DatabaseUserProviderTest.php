<?php

namespace Codemonster\Auth\Tests;

use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Database\DatabaseUser;
use Codemonster\Auth\Database\DatabaseUserProvider;
use Codemonster\Auth\Hashing\NativePasswordHasher;
use Codemonster\Database\Connection;
use PHPUnit\Framework\TestCase;

class DatabaseUserProviderTest extends TestCase
{
    public function test_it_retrieves_and_validates_database_users(): void
    {
        $hasher = new NativePasswordHasher();
        $connection = $this->connection();
        $connection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT NOT NULL, password TEXT NOT NULL)');
        $connection->table('users')->insert([
            'email' => 'admin@example.com',
            'password' => $hasher->make('secret'),
        ]);

        $provider = new DatabaseUserProvider($connection, $hasher);
        $user = $provider->retrieveByCredentials(['email' => 'admin@example.com']);

        self::assertInstanceOf(DatabaseUser::class, $user);
        self::assertSame(1, $user->getAuthIdentifier());
        self::assertSame('admin@example.com', $user->get('email'));
        self::assertTrue($provider->validateCredentials($user, ['password' => 'secret']));
        self::assertFalse($provider->validateCredentials($user, ['password' => 'wrong']));
        self::assertSame(1, $provider->retrieveById(1)?->getAuthIdentifier());
    }

    public function test_it_can_hydrate_custom_users(): void
    {
        $hasher = new NativePasswordHasher();
        $connection = $this->connection();
        $connection->statement('CREATE TABLE members (uuid TEXT PRIMARY KEY, login TEXT NOT NULL, password_hash TEXT NOT NULL)');
        $connection->table('members')->insert([
            'uuid' => 'user-1',
            'login' => 'admin',
            'password_hash' => $hasher->make('secret'),
        ]);

        $provider = new DatabaseUserProvider(
            $connection,
            $hasher,
            'members',
            'uuid',
            'password_hash',
            'login',
            fn (array $row): AuthenticatableInterface => new CustomDatabaseAuthUser($row),
        );

        $user = $provider->retrieveByCredentials(['login' => 'admin']);

        self::assertInstanceOf(CustomDatabaseAuthUser::class, $user);
        self::assertSame('user-1', $user->getAuthIdentifier());
        self::assertTrue($provider->validateCredentials($user, ['password' => 'secret']));
    }

    private function connection(): Connection
    {
        return new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}

class CustomDatabaseAuthUser implements AuthenticatableInterface
{
    /** @param array<string, mixed> $row */
    public function __construct(private array $row)
    {
    }

    public function getAuthIdentifier(): int|string
    {
        $identifier = $this->row['uuid'] ?? '';

        return is_string($identifier) ? $identifier : '';
    }

    public function getAuthPassword(): string
    {
        $password = $this->row['password_hash'] ?? '';

        return is_string($password) ? $password : '';
    }
}
