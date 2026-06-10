<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Bootstrap\ConfigCache;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class ConfigClearCommand extends Command
{
    public function getName(): string
    {
        return 'config:clear';
    }

    public function getDescription(): string
    {
        return 'Remove the cached application configuration.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!ConfigCache::clear($this->console()->getApplication()->getBasePath())) {
            throw new \RuntimeException('Unable to remove the configuration cache.');
        }

        $output->writeln('Configuration cache cleared.');

        return ExitCode::SUCCESS;
    }
}
