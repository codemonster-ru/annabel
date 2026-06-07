<?php

namespace Codemonster\Database\CLI\Commands;

use Codemonster\Database\CLI\CommandInterface;
use Codemonster\Database\Contracts\ConnectionInterface;
use PDO;

class TruncateCommand implements CommandInterface
{
    protected ConnectionInterface $connection;

    protected string $migrationsTable = 'migrations';

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function signature(): string
    {
        return 'db:truncate';
    }

    public function description(): string
    {
        return 'Truncate all tables except the migrations table (requires confirmation)';
    }

    public function handle(array $arguments): int
    {
        if (!$this->isForced($arguments) && !$this->confirmTruncate()) {
            echo "Aborted.\n";

            return 1;
        }

        $driver = $this->getDriverName();
        $tables = $this->getTables($driver);

        if (empty($tables)) {
            echo "Nothing to clean.\n";

            return 0;
        }

        $this->disableForeignKeys($driver);

        foreach ($tables as $table) {
            if ($table === $this->migrationsTable) {
                continue;
            }

            $sql = $driver === 'sqlite'
                ? sprintf('DELETE FROM %s', $this->quoteIdentifier($driver, $table))
                : sprintf('TRUNCATE TABLE %s', $this->quoteIdentifier($driver, $table));

            $this->connection->statement($sql);
        }

        $this->enableForeignKeys($driver);

        echo "Database cleaned.\n";

        return 0;
    }

    /** @param list<string> $arguments */
    protected function isForced(array $arguments): bool
    {
        return in_array('--force', $arguments, true);
    }

    protected function confirmTruncate(): bool
    {
        echo "This will delete ALL data from all tables except migrations. Type 'clean' to continue: ";

        $input = fgets(STDIN);

        if ($input === false) {
            return false;
        }

        return trim($input) === 'clean';
    }

    protected function getDriverName(): string
    {
        $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        return is_string($driver) ? $driver : '';
    }

    /**
     * @return string[]
     */
    protected function getTables(string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = $this->connection->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            );

            return $this->pluckTableNames($rows, 'name');
        }

        $rows = $this->connection->select(
            "SELECT table_name AS name FROM information_schema.tables " .
            "WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
        );

        return $this->pluckTableNames($rows, 'name');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return string[]
     */
    protected function pluckTableNames(array $rows, string $key): array
    {
        $names = [];

        foreach ($rows as $row) {
            if (is_string($row[$key] ?? null)) {
                $names[] = $row[$key];
            }
        }

        return $names;
    }

    protected function quoteIdentifier(string $driver, string $name): string
    {
        if ($driver === 'sqlite') {
            return '"' . str_replace('"', '""', $name) . '"';
        }

        return '`' . str_replace('`', '``', $name) . '`';
    }

    protected function disableForeignKeys(string $driver): void
    {
        if ($driver === 'sqlite') {
            $this->connection->statement('PRAGMA foreign_keys = OFF');

            return;
        }

        $this->connection->statement('SET FOREIGN_KEY_CHECKS = 0');
    }

    protected function enableForeignKeys(string $driver): void
    {
        if ($driver === 'sqlite') {
            $this->connection->statement('PRAGMA foreign_keys = ON');

            return;
        }

        $this->connection->statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
