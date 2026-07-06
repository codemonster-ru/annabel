<?php

declare(strict_types=1);

namespace Codemonster\Security\Tests\RateLimiting\Storage;

use Codemonster\Database\Connection;
use Codemonster\Security\RateLimiting\Storage\DatabaseThrottleStorage;
use PHPUnit\Framework\TestCase;
use Throwable;

class DatabaseThrottleStorageTest extends TestCase
{
    public function testMysqlIncrementIsAtomic(): void
    {
        if (!class_exists(Connection::class)) {
            $this->markTestSkipped('codemonster-ru/database is not installed.');
        }

        $config = $this->mysqlConfig();

        if ($config === null) {
            $this->markTestSkipped('MySQL env is not configured.');
        }

        $connection = new Connection($config);
        $table = 'throttle_requests_test_' . bin2hex(random_bytes(4));

        try {
            $connection->statement(sprintf(
                'CREATE TABLE `%s` (`key` VARCHAR(191) NOT NULL, `attempts` INT NOT NULL DEFAULT 0, `expires_at` INT NOT NULL DEFAULT 0, PRIMARY KEY (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
                $table,
            ));

            $storage = new DatabaseThrottleStorage($connection, $table);
            $key = 'login:' . bin2hex(random_bytes(4));

            $first = $storage->increment($key, 10, 1000);
            $this->assertSame(1, $first['attempts']);

            $second = $storage->increment($key, 10, 1005);
            $this->assertSame(2, $second['attempts']);

            $third = $storage->increment($key, 10, 1011);
            $this->assertSame(1, $third['attempts']);
        } finally {
            try {
                $connection->statement(sprintf('DROP TABLE IF EXISTS `%s`;', $table));
            } catch (Throwable $e) {
            }
        }
    }

    /** @return array{driver: 'mysql', host: string, port: int, database: string, username: string, password: string, charset: 'utf8mb4'}|null */
    private function mysqlConfig(): ?array
    {
        $host = getenv('MYSQL_HOST') ?: null;
        $port = getenv('MYSQL_PORT') ?: 3306;
        $database = getenv('MYSQL_DATABASE') ?: null;
        $username = getenv('MYSQL_USERNAME') ?: null;
        $password = getenv('MYSQL_PASSWORD') ?: '';

        if (!$host || !$database || !$username) {
            return null;
        }

        return [
            'driver' => 'mysql',
            'host' => $host,
            'port' => (int) $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
        ];
    }
}
