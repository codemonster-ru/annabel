<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Database\DatabaseManager;

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
    }

    public function boot(): void
    {
        // optional: nothing to boot
    }
}
