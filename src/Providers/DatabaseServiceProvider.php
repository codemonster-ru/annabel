<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Database\ConsoleDatabaseCLIKernel;
use Codemonster\Annabel\Database\LazyConnection;
use Codemonster\Annabel\Database\LazyMigrationRepository;
use Codemonster\Database\DatabaseManager;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Database\CLI\DatabaseCLIKernel;
use Codemonster\Database\Seeders\SeedPathResolver;

class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function __construct(protected $app) {}

    public function register(): void
    {
        // Register DatabaseManager as singleton
        $this->app->singleton(DatabaseManager::class, function () {
            $config = config('database') ?? [
                'default' => 'mysql',
                'connections' => [],
            ];

            return new DatabaseManager($config);
        });

        $this->app->singleton(ConnectionInterface::class, function ($app) {
            /** @var DatabaseManager $manager */
            $manager = $app->make(DatabaseManager::class);

            return new LazyConnection(fn() => $manager->connection());
        });

        $this->app->singleton(MigrationPathResolver::class, function () {
            $resolver = new MigrationPathResolver();
            $paths = config('database.migrations.paths') ?? null;

            if (is_string($paths)) {
                $paths = [$paths];
            }

            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $resolver->addPath($path);
                }
            }

            if (empty($resolver->getPaths())) {
                $resolver->addPath(base_path('database/migrations'));
            }

            return $resolver;
        });

        $this->app->singleton(\Codemonster\Database\Migrations\MigrationRepository::class, function ($app) {
            $connection = $app->make(ConnectionInterface::class);
            $table = config('database.migrations.table') ?? 'migrations';

            return new LazyMigrationRepository($connection, $table);
        });

        $this->app->singleton(SeedPathResolver::class, function () {
            $resolver = new SeedPathResolver();
            $paths = config('database.seeds.paths') ?? null;

            if (is_string($paths)) {
                $paths = [$paths];
            }

            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $resolver->addPath($path);
                }
            }

            if (empty($resolver->getPaths())) {
                $resolver->addPath(base_path('database/seeds'));
            }

            return $resolver;
        });

        $this->app->singleton(Migrator::class, function ($app) {
            $repository = $app->make(MigrationRepository::class);
            $connection = $app->make(ConnectionInterface::class);
            $paths = $app->make(MigrationPathResolver::class);

            return new Migrator($repository, $connection, $paths);
        });

        $this->app->singleton(DatabaseCLIKernel::class, function ($app) {
            $connection = $app->make(ConnectionInterface::class);
            $paths = $app->make(MigrationPathResolver::class);
            $seedPaths = $app->make(SeedPathResolver::class);

            return new ConsoleDatabaseCLIKernel($connection, $paths, $seedPaths);
        });
    }

    public function boot(): void
    {
        // optional: nothing to boot
    }
}
