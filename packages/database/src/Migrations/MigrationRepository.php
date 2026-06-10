<?php

namespace Codemonster\Database\Migrations;

use Codemonster\Database\Contracts\ConnectionInterface;
use PDO;

class MigrationRepository
{
    protected ConnectionInterface $connection;

    protected string $table;

    public function __construct(ConnectionInterface $connection, string $table = 'migrations')
    {
        $this->connection = $connection;
        $this->table = $table;

        $this->ensureTableExists();
    }

    public function ensureTableExists(): void
    {
        $driver = $this->getDriverName();

        if ($driver === 'sqlite') {
            $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS "{$this->table}" (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT,
                "migration" TEXT NOT NULL,
                "batch" INTEGER NOT NULL
            );
            SQL;
        } else {
            $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            SQL;
        }

        $this->connection->statement($sql);
    }

    protected function getDriverName(): ?string
    {
        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

            return is_string($driver) ? $driver : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get all ran migrations ordered by batch and id.
     *
     * @return array<int, array{migration:string,batch:int}>
     */
    public function getRan(): array
    {
        $rows = $this->connection->select(
            "SELECT `migration`, `batch` FROM `{$this->table}` ORDER BY `batch` ASC, `id` ASC",
        );

        $migrations = [];
        foreach ($rows as $row) {
            $migration = $row['migration'] ?? null;
            $batch = self::integerValue($row['batch'] ?? null);
            if (is_string($migration) && $batch !== null) {
                $migrations[] = ['migration' => $migration, 'batch' => $batch];
            }
        }

        return $migrations;
    }

    public function getLastBatchNumber(): int
    {
        $rows = $this->connection->select(
            "SELECT MAX(`batch`) AS batch FROM `{$this->table}`",
        );

        $batch = $rows[0]['batch'] ?? 0;

        return self::integerValue($batch) ?? 0;
    }

    /**
     * Get migrations for a given batch number.
     *
     * @return array<int, string> migration names
     */
    public function getMigrationsByBatch(int $batch): array
    {
        $rows = $this->connection->select(
            "SELECT `migration` FROM `{$this->table}` WHERE `batch` = ? ORDER BY `id` DESC",
            [$batch],
        );

        $migrations = [];
        foreach ($rows as $row) {
            if (is_string($row['migration'] ?? null)) {
                $migrations[] = $row['migration'];
            }
        }

        return $migrations;
    }

    private static function integerValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/\A-?\d+\z/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    public function log(string $migration, int $batch): void
    {
        $this->connection->statement(
            "INSERT INTO `{$this->table}` (`migration`, `batch`) VALUES (?, ?)",
            [$migration, $batch],
        );
    }

    public function delete(string $migration): void
    {
        $this->connection->statement(
            "DELETE FROM `{$this->table}` WHERE `migration` = ?",
            [$migration],
        );
    }

    /**
     * Get status map: migration => batch|null.
     *
     * @param string[] $allMigrationNames
     * @return array<int, array{migration:string,batch:?int}>
     */
    public function getStatus(array $allMigrationNames): array
    {
        $ran = $this->getRan();
        $ranMap = [];

        foreach ($ran as $item) {
            $ranMap[$item['migration']] = $item['batch'];
        }

        $status = [];

        foreach ($allMigrationNames as $name) {
            $status[] = [
                'migration' => $name,
                'batch' => $ranMap[$name] ?? null,
            ];
        }

        foreach ($ranMap as $name => $batch) {
            if (!in_array($name, $allMigrationNames, true)) {
                $status[] = [
                    'migration' => $name,
                    'batch' => $batch,
                ];
            }
        }

        return $status;
    }
}
