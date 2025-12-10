<?php

namespace Codemonster\Annabel\Console;

use Codemonster\Annabel\Console\Commands\HelpCommand;

class Console
{
    protected array $commands = [];
    protected array $aliases = [];
    protected string $defaultCommand = 'list';
    protected bool $colorsEnabled = true;

    /**
     * ANSI color codes for styling CLI output.
     */
    protected array $styles = [
        'title' => '1;36',   // bright cyan
        'label' => '1;33',   // bright yellow
        'command' => '1;32', // bright green
        'muted' => '0;37',   // light gray
        'error' => '1;31',   // bright red
    ];

    public function __construct()
    {
        $this->colorsEnabled = $this->detectColorSupport();
        $this->register(new HelpCommand());
    }

    public function register(Command $command): void
    {
        $name = $command->getName();

        $command->setConsole($this);
        $this->commands[$name] = $command;

        foreach ($command->getAliases() as $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? $this->defaultCommand;

        if (in_array($commandName, ['-h', '--help'], true)) {
            $commandName = 'help';
        }

        $command = $this->find($commandName);

        if (!$command) {
            $this->writeln($this->color("Unknown command: {$commandName}", 'error'));
            $this->writeln('');
            $this->showDefaultHelp();

            return 1;
        }

        return $command->handle(array_slice($argv, 2));
    }

    public function find(string $name): ?Command
    {
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        if (isset($this->aliases[$name])) {
            $canonical = $this->aliases[$name];

            return $this->commands[$canonical] ?? null;
        }

        return null;
    }

    /**
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        $commands = $this->commands;
        ksort($commands);

        return $commands;
    }

    /**
     * @return string[]
     */
    public function getAliasesFor(string $commandName): array
    {
        $aliases = [];

        foreach ($this->aliases as $alias => $canonical) {
            if ($canonical === $commandName) {
                $aliases[] = $alias;
            }
        }

        sort($aliases);

        return $aliases;
    }

    public function getVersion(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion('codemonster-ru/annabel');

                if ($version) {
                    return $version;
                }
            } catch (\Throwable) {
                // Ignore lookup errors and fall back to dev.
            }
        }

        return 'dev';
    }

    public function writeln(string $line = ''): void
    {
        echo $line . PHP_EOL;
    }

    public function color(string $text, string $style): string
    {
        if (!$this->colorsEnabled || !isset($this->styles[$style])) {
            return $text;
        }

        return "\033[{$this->styles[$style]}m{$text}\033[0m";
    }

    protected function detectColorSupport(): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (!function_exists('stream_isatty')) {
            return true;
        }

        return stream_isatty(\STDOUT);
    }

    protected function showDefaultHelp(): void
    {
        $help = $this->find('help') ?? $this->find($this->defaultCommand);

        if ($help) {
            $help->handle();
        }
    }
}
