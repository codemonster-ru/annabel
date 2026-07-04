<?php

namespace Codemonster\Cms\Modules\Setup\Services;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Auth\Services\AdminAccountCreator;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Cms\Support\Installation\InstallationState;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\DatabaseManager;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Database\Seeders\SeederRunner;
use Codemonster\Database\Seeders\SeedPathResolver;

class CmsInstaller
{
    public function __construct(
        private Application $app,
        private EnvironmentFile $env,
        private InstallationState $state,
        private ModuleManager $modules,
        private AdminAccountCreator $admins,
    ) {
    }

    /**
     * @param array<string, mixed> $database
     * @param array{username: string, email: string, password: string} $admin
     */
    public function install(array $database, array $admin): void
    {
        $this->env->write([
            'DB_HOST' => $database['host'],
            'DB_PORT' => $database['port'],
            'DB_DATABASE' => $database['database'],
            'DB_USERNAME' => $database['username'],
            'DB_PASSWORD' => $database['password'],
        ]);

        $this->withRuntimeDatabase($database, function () use ($admin): void {
            $connection = app(ConnectionInterface::class);

            $this->runMigrations($connection);
            $this->runSeeds($connection);
            $this->admins->create($admin);
        });

        $this->state->markInstalled([
            'admin_email' => $admin['email'],
        ]);
    }

    /**
     * @param array<string, mixed> $database
     */
    private function withRuntimeDatabase(array $database, callable $callback): void
    {
        $container = $this->app->getContainer();
        $originalManager = $container->make(DatabaseManager::class);
        $originalConnection = $container->make(ConnectionInterface::class);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => $database,
        ]);

        $manager = new DatabaseManager((array) config('database'));
        $connection = $manager->connection('mysql');

        $container->instance(DatabaseManager::class, $manager);
        $container->instance(ConnectionInterface::class, $connection);

        try {
            $callback();
        } finally {
            $container->instance(DatabaseManager::class, $originalManager);
            $container->instance(ConnectionInterface::class, $originalConnection);
        }
    }

    private function runMigrations(ConnectionInterface $connection): void
    {
        $paths = new MigrationPathResolver();

        foreach ($this->modules->migrationPaths() as $path) {
            $paths->addPath($path);
        }

        $repository = new MigrationRepository(
            $connection,
            (string) config('database.migrations.table', 'migrations'),
        );

        (new Migrator($repository, $connection, $paths))->migrate();
    }

    private function runSeeds(ConnectionInterface $connection): void
    {
        $paths = new SeedPathResolver();

        foreach ($this->modules->seedPaths() as $path) {
            $paths->addPath($path);
        }

        (new SeederRunner($connection, $paths))->seed();
    }
}
