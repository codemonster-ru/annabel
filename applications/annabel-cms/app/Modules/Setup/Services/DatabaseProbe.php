<?php

namespace Codemonster\Cms\Modules\Setup\Services;

use Codemonster\Database\Connection;
use Throwable;

class DatabaseProbe
{
    /**
     * @param array<string, mixed> $config
     */
    public function assertReady(array $config): void
    {
        $connection = new Connection($config);
        $table = 'annabel_setup_permission_check_' . bin2hex(random_bytes(6));
        $identifier = $this->quoteIdentifier($table);
        $created = false;

        $connection->select('SELECT 1');

        try {
            $connection->statement("CREATE TABLE {$identifier} (id INT NOT NULL PRIMARY KEY)");
            $created = true;
            $connection->statement("ALTER TABLE {$identifier} ADD COLUMN value VARCHAR(16) NULL");
        } finally {
            if ($created) {
                $connection->statement("DROP TABLE {$identifier}");
            }
        }

        $this->assertEmpty($connection);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function errorMessage(Throwable $e, array $config): string
    {
        $message = $e->getMessage();
        $debug = env('APP_DEBUG', false, true);
        $host = (string) ($config['host'] ?? '');

        if (str_contains($message, '[2002] No such file or directory')) {
            return $host === 'localhost'
                ? 'Unable to connect to MySQL through the local socket. Use 127.0.0.1 instead of localhost, or check the database host.'
                : 'Unable to connect to MySQL through the local socket. Check the database host or socket configuration.';
        }

        return $debug
            ? 'Unable to verify database access: ' . $message
            : 'Unable to verify database access. Check the host, port, database name, username, and password.';
    }

    private function assertEmpty(Connection $connection): void
    {
        if ($connection->select('SHOW TABLES') !== []) {
            throw new \RuntimeException(
                'The selected database is not empty. Use an empty database before continuing.',
            );
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
