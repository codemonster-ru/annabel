<?php

namespace Codemonster\Annabel\Database;

use Codemonster\Database\Migrations\MigrationRepository;

/**
 * Migration repository that defers table creation until it's actually needed.
 */
class LazyMigrationRepository extends MigrationRepository
{
    protected bool $initialized = false;

    public function __construct(\Codemonster\Database\Contracts\ConnectionInterface $connection, string $table = 'migrations')
    {
        // Avoid eager table creation from parent::__construct
        $this->connection = $connection;
        $this->table = $table;
    }

    protected function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        parent::ensureTableExists();
        $this->initialized = true;
    }

    public function ensureTableExists(): void
    {
        $this->ensureInitialized();
    }

    public function getRan(): array
    {
        $this->ensureInitialized();

        return parent::getRan();
    }

    public function getLastBatchNumber(): int
    {
        $this->ensureInitialized();

        return parent::getLastBatchNumber();
    }

    public function getMigrationsByBatch(int $batch): array
    {
        $this->ensureInitialized();

        return parent::getMigrationsByBatch($batch);
    }

    public function log(string $migration, int $batch): void
    {
        $this->ensureInitialized();

        parent::log($migration, $batch);
    }

    public function delete(string $migration): void
    {
        $this->ensureInitialized();

        parent::delete($migration);
    }

    public function getStatus(array $allMigrationNames): array
    {
        $this->ensureInitialized();

        return parent::getStatus($allMigrationNames);
    }
}
