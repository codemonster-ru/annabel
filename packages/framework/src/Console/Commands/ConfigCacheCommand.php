<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Bootstrap\ConfigCache;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class ConfigCacheCommand extends Command
{
    public function getName(): string
    {
        return 'config:cache';
    }

    public function getDescription(): string
    {
        return 'Cache application configuration.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = ConfigCache::write($this->console()->getApplication()->getBasePath());
        $output->writeln("Cached {$count} configuration file(s).");

        return ExitCode::SUCCESS;
    }
}
