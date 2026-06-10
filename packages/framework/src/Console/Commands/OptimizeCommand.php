<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Bootstrap\ConfigCache;
use Codemonster\Annabel\Bootstrap\RouteCache;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class OptimizeCommand extends Command
{
    public function getName(): string
    {
        return 'optimize';
    }

    public function getDescription(): string
    {
        return 'Cache configuration and routes for production.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = $this->console()->getApplication();
        $basePath = $app->getBasePath();
        $configCount = ConfigCache::write($basePath);

        try {
            $routeCount = RouteCache::write($basePath, $app->getKernel()->getRouter());
        } catch (\Throwable $e) {
            ConfigCache::clear($basePath);

            throw $e;
        }

        $output->writeln("Optimized application: {$configCount} config file(s), {$routeCount} route(s).");

        return ExitCode::SUCCESS;
    }
}
