<?php

namespace Codemonster\Security\RateLimiting\Storage;

use Codemonster\Database\Contracts\ConnectionInterface;
use PDO;

class DatabaseThrottleStorage implements AtomicThrottleStorageInterface
{
    protected ConnectionInterface $connection;
    protected string $table;

    public function __construct(ConnectionInterface $connection, string $table = 'throttle_requests')
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function get(string $key): ?array
    {
        $record = $this->connection->table($this->table)->where('key', $key)->first();

        return $record === null ? null : $this->normalizeRecord($record);
    }

    public function put(string $key, array $value): void
    {
        $record = $value;

        $existing = $this->connection->table($this->table)->where('key', $key)->first();

        if ($existing) {
            $this->connection->table($this->table)->where('key', $key)->update($record);

            return;
        }

        $this->connection->table($this->table)->insert(array_merge(['key' => $key], $record));
    }

    public function forget(string $key): void
    {
        $this->connection->table($this->table)->where('key', $key)->delete();
    }

    public function increment(string $key, int $decaySeconds, int $now): array
    {
        $expiresAt = $now + $decaySeconds;
        $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $sql = sprintf(
                'INSERT INTO `%s` (`key`, `attempts`, `expires_at`) VALUES (?, 1, ?)
                ON DUPLICATE KEY UPDATE
                    `attempts` = IF(`expires_at` <= ?, 1, `attempts` + 1),
                    `expires_at` = IF(`expires_at` <= ?, ?, `expires_at`)',
                $this->table,
            );

            $this->connection->statement($sql, [$key, $expiresAt, $now, $now, $expiresAt]);

            $record = $this->connection->table($this->table)->where('key', $key)->first();

            return $record === null
                ? ['attempts' => 1, 'expires_at' => $expiresAt]
                : $this->normalizeRecord($record);
        }

        $record = ['attempts' => 1, 'expires_at' => $expiresAt];

        $this->connection->transaction(function (ConnectionInterface $connection) use ($key, $decaySeconds, $now, &$record) {
            $row = $connection->table($this->table)->where('key', $key)->first();
            $expiresAt = $now + $decaySeconds;

            if (!$row || self::integerValue($row['expires_at'] ?? null, 0) <= $now) {
                $record = ['attempts' => 1, 'expires_at' => $expiresAt];

                if ($row) {
                    $connection->table($this->table)->where('key', $key)->update($record);
                } else {
                    $connection->table($this->table)->insert(array_merge(['key' => $key], $record));
                }

                return;
            }

            $attempts = self::integerValue($row['attempts'] ?? null, 0) + 1;
            $record = [
                'attempts' => $attempts,
                'expires_at' => self::integerValue($row['expires_at'] ?? null, $expiresAt),
            ];

            $connection->table($this->table)->where('key', $key)->update(['attempts' => $attempts]);
        });

        return $record;
    }

    /** @param array<mixed, mixed> $record
     *  @return array{attempts: int, expires_at: int}
     */
    private function normalizeRecord(array $record): array
    {
        return [
            'attempts' => self::integerValue($record['attempts'] ?? null, 0),
            'expires_at' => self::integerValue($record['expires_at'] ?? null, 0),
        ];
    }

    private static function integerValue(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/\A-?\d+\z/', $value) === 1) {
            return (int) $value;
        }

        return $default;
    }
}
