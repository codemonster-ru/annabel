<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;

class AboutCommand extends Command
{
    public function getName(): string
    {
        return 'about';
    }

    public function getDescription(): string
    {
        return 'Show basic information about the Annabel application.';
    }

    public function handle(array $arguments = []): int
    {
        $console = $this->console();
        $app = $console->getApplication();

        $console->writeln($console->color('About', 'label'));
        $console->writeln('  Version: ' . $console->color($console->getVersion(), 'command'));
        $console->writeln('  Base path: ' . $app->getBasePath());

        $providers = $app->getProviders();
        $console->writeln('  Providers: ' . count($providers));

        foreach ($providers as $provider) {
            $console->writeln('    - ' . get_class($provider));
        }

        return 0;
    }
}
