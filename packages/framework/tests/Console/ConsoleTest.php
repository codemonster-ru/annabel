<?php

namespace Codemonster\Annabel\Tests\Console;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Console\BufferedOutput;
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Console;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    private array $paths = [];

    protected function setUp(): void
    {
        Application::resetInstance();
    }

    protected function tearDown(): void
    {
        Application::resetInstance();

        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_default_command_prints_help(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn() => $console->run(['annabel']));

        $this->assertStringContainsString('Annabel CLI', $output);
        $this->assertStringContainsString('Available commands:', $output);
    }

    public function test_unknown_command_displays_error_and_help(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn() => $console->run(['annabel', 'missing']));

        $this->assertStringContainsString('Unknown command: missing', $output);
        $this->assertStringContainsString('Available commands:', $output);
    }

    public function test_help_alias_is_resolved(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn() => $console->run(['annabel', 'help']));

        $this->assertStringContainsString('Annabel CLI', $output);
        $this->assertStringContainsString('list', $output);
    }

    public function test_registered_commands_include_database_commands(): void
    {
        $console = new Console();
        $commandNames = array_keys($console->getCommands());
        $expected = [
            'list',
            'about',
            'route:list',
            'config:get',
            'container:list',
            'vendor:publish',
            'serve',
            'make:migration',
            'migrate',
            'migrate:rollback',
            'migrate:status',
            'make:seed',
            'seed',
            'db:wipe',
            'db:truncate',
        ];

        foreach ($expected as $name) {
            $this->assertContains($name, $commandNames, "Missing command [{$name}].");
        }
    }

    public function test_registered_commands_match_expected_list(): void
    {
        $console = new Console();
        $commandNames = array_keys($console->getCommands());
        $expected = [
            'list',
            'about',
            'route:list',
            'config:get',
            'container:list',
            'vendor:publish',
            'serve',
            'make:migration',
            'migrate',
            'migrate:rollback',
            'migrate:status',
            'make:seed',
            'seed',
            'db:wipe',
            'db:truncate',
        ];

        sort($commandNames);
        sort($expected);

        $this->assertSame($expected, $commandNames);
    }

    public function test_command_aliases_are_registered(): void
    {
        $console = new Console();

        $this->assertSame(['help'], $console->getAliasesFor('list'));
        $this->assertSame([], $console->getAliasesFor('about'));
    }

    public function test_vendor_publish_requires_explicit_selector(): void
    {
        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $console = new Console();
        $console->setApplication($app);

        $exitCode = null;
        $output = $this->captureOutput(function () use ($console, &$exitCode) {
            $exitCode = $console->run(['annabel', 'vendor:publish']);
        });

        $this->assertSame(ExitCode::INVALID, $exitCode);
        $this->assertStringContainsString('Select resources', $output);
    }

    public function test_vendor_publish_publishes_matching_tag(): void
    {
        $basePath = $this->directory('annabel-console-app-');
        $source = tempnam(sys_get_temp_dir(), 'annabel-console-source-');
        file_put_contents($source, 'published');
        $this->paths[] = $source;
        $destination = $basePath . '/config/example.php';
        $app = new Application($basePath, null, false);
        $app->make(PublishRegistry::class)->add(self::class, [
            $source => $destination,
        ], 'config');
        $console = new Console();
        $console->setApplication($app);

        $exitCode = null;
        $output = $this->captureOutput(function () use ($console, &$exitCode) {
            $exitCode = $console->run([
                'annabel',
                'vendor:publish',
                '--tag=config',
            ]);
        });

        $this->assertSame(0, $exitCode);
        $this->assertSame('published', file_get_contents($destination));
        $this->assertStringContainsString('Published 1 file(s)', $output);

        file_put_contents($source, 'forced');
        $this->captureOutput(fn() => $console->run([
            'annabel',
            'vendor:publish',
            '--tag',
            'config',
            '--force',
        ]));

        $this->assertSame('forced', file_get_contents($destination));
    }

    public function test_provider_commands_are_resolved_with_dependency_injection()
    {
        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $app->getContainer()->instance(TestCliDependency::class, new TestCliDependency('injected'));
        (new TestCliServiceProvider($app))->register();
        $output = new BufferedOutput();
        $console = new Console($output);
        $console->setApplication($app);

        $exitCode = $console->run([
            'annabel',
            'example:di',
            'argument',
            '--name=Annabel',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $exitCode);
        $this->assertStringContainsString(
            'injected:argument:Annabel',
            $output->content()
        );
    }

    public function test_command_exceptions_are_converted_to_failure_exit_code()
    {
        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $app->make(\Codemonster\Annabel\Console\CommandRegistry::class)
            ->add(TestFailingCliCommand::class);
        $output = new BufferedOutput();
        $console = new Console($output);
        $console->setApplication($app);

        $exitCode = $console->run(['annabel', 'example:fail']);

        $this->assertSame(ExitCode::FAILURE, $exitCode);
        $this->assertStringContainsString('Command [example:fail] failed: Boom', $output->content());
    }

    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();

        return ob_get_clean() ?: '';
    }

    private function directory(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . bin2hex(random_bytes(6));
        mkdir($path, 0770, true);
        $this->paths[] = $path . '/config/example.php';
        $this->paths[] = $path . '/config';
        $this->paths[] = $path;

        return $path;
    }
}

class TestCliDependency
{
    public function __construct(public string $value) {}
}

class TestCliCommand extends Command
{
    public function __construct(private TestCliDependency $dependency) {}

    public function getName(): string
    {
        return 'example:di';
    }

    public function getDescription(): string
    {
        return 'Test provider command dependency injection.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf(
            '%s:%s:%s',
            $this->dependency->value,
            $input->arguments()[0] ?? '',
            $input->option('name', '')
        ));

        return ExitCode::SUCCESS;
    }
}

class TestCliServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands(TestCliCommand::class);
    }
}

class TestFailingCliCommand extends Command
{
    public function getName(): string
    {
        return 'example:fail';
    }

    public function getDescription(): string
    {
        return 'Test failed command handling.';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        throw new \RuntimeException('Boom');
    }
}
