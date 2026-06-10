<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Bootstrap\ConfigCache;
use Codemonster\Annabel\Bootstrap\RouteCache;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class OptimizeClearCommand extends Command
{
    public function getName(): string
    {
        return 'optimize:clear';
    }

    public function getDescription(): string
    {
        return 'Remove configuration and route caches.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $basePath = $this->console()->getApplication()->getBasePath();
        $configCleared = ConfigCache::clear($basePath);
        $routesCleared = RouteCache::clear($basePath);

        if (!$configCleared || !$routesCleared) {
            throw new \RuntimeException('Unable to remove all optimization caches.');
        }

        $output->writeln('Optimization caches cleared.');

        return ExitCode::SUCCESS;
    }
}
