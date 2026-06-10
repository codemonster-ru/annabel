<?php

namespace Codemonster\Annabel\Console;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Console\Commands\AboutCommand;
use Codemonster\Annabel\Console\Commands\ConfigCacheCommand;
use Codemonster\Annabel\Console\Commands\ConfigClearCommand;
use Codemonster\Annabel\Console\Commands\ConfigGetCommand;
use Codemonster\Annabel\Console\Commands\ConfigListCommand;
use Codemonster\Annabel\Console\Commands\ContainerListCommand;
use Codemonster\Annabel\Console\Commands\DatabaseCommand;
use Codemonster\Annabel\Console\Commands\HelpCommand;
use Codemonster\Annabel\Console\Commands\MakeControllerCommand;
use Codemonster\Annabel\Console\Commands\MakeJobCommand;
use Codemonster\Annabel\Console\Commands\MakeMiddlewareCommand;
use Codemonster\Annabel\Console\Commands\MakeModelCommand;
use Codemonster\Annabel\Console\Commands\MakePolicyCommand;
use Codemonster\Annabel\Console\Commands\MakeRequestCommand;
use Codemonster\Annabel\Console\Commands\OptimizeClearCommand;
use Codemonster\Annabel\Console\Commands\OptimizeCommand;
use Codemonster\Annabel\Console\Commands\QueueFailedCommand;
use Codemonster\Annabel\Console\Commands\QueueFlushCommand;
use Codemonster\Annabel\Console\Commands\QueueRetryCommand;
use Codemonster\Annabel\Console\Commands\QueueWorkCommand;
use Codemonster\Annabel\Console\Commands\RouteCacheCommand;
use Codemonster\Annabel\Console\Commands\RouteClearCommand;
use Codemonster\Annabel\Console\Commands\RouteListCommand;
use Codemonster\Annabel\Console\Commands\ScheduleListCommand;
use Codemonster\Annabel\Console\Commands\ScheduleRunCommand;
use Codemonster\Annabel\Console\Commands\ServeCommand;
use Codemonster\Annabel\Console\Commands\VendorPublishCommand;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Database\Console\DatabaseConsoleKernel;

class Console
{
    /** @var array<string, Command> */
    protected array $commands = [];
    /** @var array<string, string> */
    protected array $aliases = [];
    protected string $defaultCommand = 'list';
    protected bool $colorsEnabled = true;
    protected ?Application $app = null;
    protected bool $databaseCommandsLoaded = false;
    protected bool $applicationCommandsLoaded = false;
    protected OutputInterface $output;

    /**
     * ANSI color codes for styling CLI output.
     *
     * @var array<string, string>
     */
    protected array $styles = [
        'title' => '1;36',   // bright cyan
        'label' => '1;33',   // bright yellow
        'command' => '1;32', // bright green
        'muted' => '0;37',   // light gray
        'error' => '1;31',   // bright red
    ];

    public function __construct(?OutputInterface $output = null)
    {
        $this->output = $output ?? new StreamOutput();
        $this->colorsEnabled = $this->detectColorSupport();
        $this->register(new HelpCommand());
        $this->register(new AboutCommand());
        $this->register(new RouteListCommand());
        $this->register(new RouteCacheCommand());
        $this->register(new RouteClearCommand());
        $this->register(new ConfigCacheCommand());
        $this->register(new ConfigClearCommand());
        $this->register(new ConfigGetCommand());
        $this->register(new ConfigListCommand());
        $this->register(new ContainerListCommand());
        $this->register(new ServeCommand());
        $this->register(new VendorPublishCommand());
        $this->register(new OptimizeCommand());
        $this->register(new OptimizeClearCommand());
        $this->register(new QueueFailedCommand());
        $this->register(new QueueFlushCommand());
        $this->register(new QueueRetryCommand());
        $this->register(new QueueWorkCommand());
        $this->register(new ScheduleListCommand());
        $this->register(new ScheduleRunCommand());
        $this->register(new MakeControllerCommand());
        $this->register(new MakeJobCommand());
        $this->register(new MakeModelCommand());
        $this->register(new MakeMiddlewareCommand());
        $this->register(new MakeRequestCommand());
        $this->register(new MakePolicyCommand());
    }

    public function register(Command|string $command): void
    {
        $command = $this->resolveCommand($command);
        $name = $command->getName();

        if (isset($this->commands[$name]) && $this->commands[$name] !== $command) {
            throw new \RuntimeException("Console command [$name] is already registered.");
        }

        $command->setConsole($this);

        $this->commands[$name] = $command;

        foreach ($command->getAliases() as $alias) {
            if (isset($this->aliases[$alias]) && $this->aliases[$alias] !== $name) {
                throw new \RuntimeException("Console command alias [$alias] is already registered.");
            }

            $this->aliases[$alias] = $name;
        }
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $input = new ArgvInput($argv, $this->defaultCommand);
        $commandName = $input->command();

        if (in_array($commandName, ['-h', '--help'], true)) {
            $commandName = 'help';
            $input->setCommand($commandName);
        }

        $this->ensureExtensionCommandsRegistered();

        $command = $this->find($commandName);

        if (!$command) {
            $this->writeln($this->color("Unknown command: {$commandName}", 'error'));
            $this->writeln('');
            $this->showDefaultHelp();

            return ExitCode::FAILURE;
        }

        try {
            return $command->execute($input, $this->output);
        } catch (\Throwable $e) {
            $this->writeln($this->color(
                sprintf('Command [%s] failed: %s', $command->getName(), $e->getMessage()),
                'error',
            ));

            return ExitCode::FAILURE;
        }
    }

    public function getApplication(): Application
    {
        if ($this->app) {
            return $this->app;
        }

        try {
            return $this->app = Application::getInstance();
        } catch (\Throwable) {
            // Continue to attempt to bootstrap manually.
        }

        $basePath = getcwd() ?: dirname(__DIR__, 2);
        $bootstrapFile = $basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

        if (is_file($bootstrapFile)) {
            $app = require $bootstrapFile;

            if ($app instanceof Application) {
                return $this->app = $app;
            }
        }

        return $this->app = new Application($basePath);
    }

    public function setApplication(Application $app): void
    {
        $this->app = $app;
        $this->applicationCommandsLoaded = false;
        $this->databaseCommandsLoaded = false;
    }

    public function find(string $name): ?Command
    {
        $this->ensureExtensionCommandsRegistered();

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
        $this->ensureExtensionCommandsRegistered();

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
        $this->output->writeln($line);
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

    protected function ensureDatabaseCommandsRegistered(): void
    {
        if ($this->databaseCommandsLoaded) {
            return;
        }

        $this->databaseCommandsLoaded = true;

        if (!class_exists(DatabaseConsoleKernel::class)) {
            return;
        }

        try {
            /** @var DatabaseConsoleKernel $dbKernel */
            $dbKernel = $this->getApplication()->make(DatabaseConsoleKernel::class);
            $registry = $dbKernel->getRegistry();

            foreach ($registry->all() as $cmd) {
                $this->register(new DatabaseCommand($cmd->signature(), $cmd->description()));
            }
        } catch (\Throwable $e) {
            // Silently ignore registration failures to keep CLI functional without DB config.
        }
    }

    protected function ensureApplicationCommandsRegistered(): void
    {
        if ($this->applicationCommandsLoaded) {
            return;
        }

        $this->applicationCommandsLoaded = true;
        $app = $this->getApplication();
        /** @var CommandRegistry $registry */
        $registry = $app->make(CommandRegistry::class);

        foreach ($registry->all() as $command) {
            $this->register($command);
        }
    }

    protected function ensureExtensionCommandsRegistered(): void
    {
        $this->ensureApplicationCommandsRegistered();
        $this->ensureDatabaseCommandsRegistered();
    }

    protected function resolveCommand(Command|string $command): Command
    {
        if ($command instanceof Command) {
            return $command;
        }

        $resolved = $this->getApplication()->make($command);

        if (!$resolved instanceof Command) {
            throw new \RuntimeException("Console command [$command] must extend " . Command::class . '.');
        }

        return $resolved;
    }

    protected function showDefaultHelp(): void
    {
        $help = $this->find('help') ?? $this->find($this->defaultCommand);

        if ($help) {
            $help->execute(new ArgvInput(['annabel', 'list']), $this->output);
        }
    }
}
