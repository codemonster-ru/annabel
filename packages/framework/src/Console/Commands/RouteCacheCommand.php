<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Bootstrap\RouteCache;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class RouteCacheCommand extends Command
{
    public function getName(): string
    {
        return 'route:cache';
    }

    public function getDescription(): string
    {
        return 'Cache application routes.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = $this->console()->getApplication();
        $count = RouteCache::write($app->getBasePath(), $app->getKernel()->getRouter());
        $output->writeln("Cached {$count} route(s).");

        return ExitCode::SUCCESS;
    }
}
