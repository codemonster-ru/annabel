<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;

class HelpCommand extends Command
{
    public function getName(): string
    {
        return 'list';
    }

    public function getAliases(): array
    {
        return ['help'];
    }

    public function getDescription(): string
    {
        return 'Show available Annabel CLI commands and usage information.';
    }

    public function getUsage(): string
    {
        return 'list [command]';
    }

    public function handle(array $arguments = []): int
    {
        $target = $arguments[0] ?? null;
        $console = $this->console();

        if ($target) {
            $command = $console->find($target);

            if (!$command) {
                $console->writeln("Command [{$target}] not found.");
                $console->writeln('');
            } else {
                $this->renderCommandHelp($command);

                return 0;
            }
        }

        $this->renderApplicationHelp();

        return 0;
    }

    protected function renderApplicationHelp(): void
    {
        $console = $this->console();
        $version = $console->getVersion();

        $console->writeln($console->color(" Annabel CLI ", 'title') . $console->color("({$version})", 'muted'));
        $console->writeln($console->color(str_repeat('-', 48), 'muted'));
        $console->writeln($console->color('Usage:', 'label'));
        $console->writeln('  ' . $console->color('php vendor/bin/annabel [command]', 'command'));
        $console->writeln('');
        $console->writeln($console->color('Available commands:', 'label'));

        foreach ($console->getCommands() as $command) {
            $aliases = $console->getAliasesFor($command->getName());
            $aliasText = $aliases ? ' [' . implode(', ', $aliases) . ']' : '';

            $console->writeln(sprintf(
                '  %s  %s%s',
                $console->color(str_pad($command->getName(), 12), 'command'),
                $command->getDescription(),
                $aliasText ? ' ' . $console->color($aliasText, 'muted') : ''
            ));
        }

        $console->writeln('');
        $console->writeln($console->color('Examples:', 'label'));
        $console->writeln('  ' . $console->color('php vendor/bin/annabel', 'command'));
        $console->writeln('  ' . $console->color('php vendor/bin/annabel help', 'command'));
        $console->writeln('  ' . $console->color('php vendor/bin/annabel help list', 'command'));
    }

    protected function renderCommandHelp(Command $command): void
    {
        $console = $this->console();
        $aliases = $console->getAliasesFor($command->getName());
        $version = $console->getVersion();

        $console->writeln($console->color(" Annabel CLI ", 'title') . $console->color("({$version})", 'muted'));
        $console->writeln($console->color(str_repeat('-', 48), 'muted'));
        $console->writeln($console->color('Command:', 'label') . ' ' . $console->color($command->getName(), 'command'));

        if ($aliases) {
            $console->writeln($console->color('Aliases:', 'label') . ' ' . implode(', ', $aliases));
        }

        $console->writeln($console->color('Description:', 'label') . ' ' . $command->getDescription());
        $console->writeln($console->color('Usage:', 'label') . ' php vendor/bin/annabel ' . ($command->getUsage() ?: $command->getName()));
    }
}
