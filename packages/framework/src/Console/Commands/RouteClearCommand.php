<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Bootstrap\RouteCache;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class RouteClearCommand extends Command
{
    public function getName(): string
    {
        return 'route:clear';
    }

    public function getDescription(): string
    {
        return 'Remove the cached application routes.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!RouteCache::clear($this->console()->getApplication()->getBasePath())) {
            throw new \RuntimeException('Unable to remove the route cache.');
        }

        $output->writeln('Route cache cleared.');

        return ExitCode::SUCCESS;
    }
}
