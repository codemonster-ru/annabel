<?php

namespace Codemonster\Annabel\Database;

use Codemonster\Database\CLI\CommandRegistry;
use Codemonster\Database\CLI\DatabaseCLIKernel;
use Codemonster\Database\CLI\Commands\MakeMigrationCommand;
use Codemonster\Database\CLI\Commands\MigrateCommand;
use Codemonster\Database\CLI\Commands\RollbackCommand;
use Codemonster\Database\CLI\Commands\StatusCommand;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Database\Migrations\MigrationRepository;

/**
 * Custom kernel that avoids touching the database until a command executes.
 */
class ConsoleDatabaseCLIKernel extends DatabaseCLIKernel
{
    public function __construct(ConnectionInterface $connection, ?MigrationPathResolver $paths = null)
    {
        $this->paths = $paths ?? new MigrationPathResolver();

        if (empty($this->paths->getPaths())) {
            $this->paths->addPath(getcwd() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        }

        $repository = new LazyMigrationRepository($connection);

        $this->migrator = new Migrator($repository, $connection, $this->paths);
        $this->commands = new CommandRegistry();

        $this->registerDefaultCommands();
    }

    protected function registerDefaultCommands(): void
    {
        $this->commands->register(new MigrateCommand($this->migrator));
        $this->commands->register(new RollbackCommand($this->migrator));
        $this->commands->register(new StatusCommand($this->migrator));
        $this->commands->register(new MakeMigrationCommand($this->paths));
    }

    public function getRegistry(): CommandRegistry
    {
        return $this->commands;
    }

    public function getPathResolver(): MigrationPathResolver
    {
        return $this->paths;
    }

    public function getMigrator(): Migrator
    {
        return $this->migrator;
    }
}
