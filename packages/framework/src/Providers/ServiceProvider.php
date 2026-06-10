<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\CommandRegistry;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Publishing\PublishRegistry;

abstract class ServiceProvider implements ServiceProviderInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
    }

    protected function app(): Application
    {
        return $this->app;
    }

    /**
     * @param array<string, string> $paths
     * @param string|list<string> $tags
     */
    protected function publishes(array $paths, string|array $tags = []): void
    {
        $this->app->make(PublishRegistry::class)->add(static::class, $paths, $tags);
    }

    /**
     * @param class-string<Command>|Command|list<class-string<Command>|Command> $commands
     */
    protected function commands(string|Command|array $commands): void
    {
        $this->app->make(CommandRegistry::class)->add($commands);
    }
}
