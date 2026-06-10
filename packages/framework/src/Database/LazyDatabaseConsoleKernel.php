<?php

namespace Codemonster\Annabel\Database;

use Codemonster\Database\Console\CommandRegistry;
use Codemonster\Database\Console\Commands\MakeMigrationCommand;
use Codemonster\Database\Console\Commands\MakeSeedCommand;
use Codemonster\Database\Console\Commands\MigrateCommand;
use Codemonster\Database\Console\Commands\RollbackCommand;
use Codemonster\Database\Console\Commands\SeedCommand;
use Codemonster\Database\Console\Commands\StatusCommand;
use Codemonster\Database\Console\Commands\TruncateCommand;
use Codemonster\Database\Console\Commands\WipeCommand;
use Codemonster\Database\Console\DatabaseConsoleKernel;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Database\Seeders\SeederRunner;
use Codemonster\Database\Seeders\SeedPathResolver;

/**
 * Custom kernel that avoids touching the database until a command executes.
 */
class LazyDatabaseConsoleKernel extends DatabaseConsoleKernel
{
    public function __construct(
        ConnectionInterface $connection,
        ?MigrationPathResolver $paths = null,
        ?SeedPathResolver $seedPaths = null,
    ) {
        $this->paths = $paths ?? new MigrationPathResolver();

        if (empty($this->paths->getPaths())) {
            $this->paths->addPath(getcwd() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        }

        $this->seedPaths = $seedPaths ?? new SeedPathResolver();

        if (empty($this->seedPaths->getPaths())) {
            $this->seedPaths->addPath(getcwd() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds');
        }

        $repository = new LazyMigrationRepository($connection);

        $this->migrator = new Migrator($repository, $connection, $this->paths);
        $this->seeder = new SeederRunner($connection, $this->seedPaths);
        $this->commands = new CommandRegistry();

        $this->registerDefaultCommands();
    }

    protected function registerDefaultCommands(): void
    {
        $this->commands->register(new MigrateCommand($this->migrator));
        $this->commands->register(new RollbackCommand($this->migrator));
        $this->commands->register(new StatusCommand($this->migrator));
        $this->commands->register(new MakeMigrationCommand($this->paths));
        $this->commands->register(new SeedCommand($this->seeder));
        $this->commands->register(new MakeSeedCommand($this->seedPaths));
        $this->commands->register(new WipeCommand($this->migrator->getConnection()));
        $this->commands->register(new TruncateCommand($this->migrator->getConnection()));
    }

    public function getRegistry(): CommandRegistry
    {
        return $this->commands;
    }

    public function getPathResolver(): MigrationPathResolver
    {
        return $this->paths;
    }

    public function getSeedPathResolver(): SeedPathResolver
    {
        return $this->seedPaths;
    }

    public function getMigrator(): Migrator
    {
        return $this->migrator;
    }

    public function getSeeder(): SeederRunner
    {
        return $this->seeder;
    }
}
