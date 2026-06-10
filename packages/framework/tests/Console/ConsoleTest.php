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
use Codemonster\Config\Config;
use Codemonster\Database\Connection;
use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\WorkableQueueInterface;
use Codemonster\Queue\JobResult;
use Codemonster\Queue\JobSerializer;
use Codemonster\Queue\QueuedJob;
use Codemonster\Queue\QueueManager;
use Codemonster\Queue\Worker;
use Codemonster\Scheduler\Schedule;
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
        Config::reset();
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
        $output = $this->captureOutput(fn () => $console->run(['annabel']));

        $this->assertStringContainsString('Annabel CLI', $output);
        $this->assertStringContainsString('Available commands:', $output);
    }

    public function test_unknown_command_displays_error_and_help(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn () => $console->run(['annabel', 'missing']));

        $this->assertStringContainsString('Unknown command: missing', $output);
        $this->assertStringContainsString('Available commands:', $output);
    }

    public function test_help_alias_is_resolved(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn () => $console->run(['annabel', 'help']));

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
            'route:cache',
            'route:clear',
            'config:cache',
            'config:clear',
            'config:get',
            'config:list',
            'container:list',
            'vendor:publish',
            'optimize',
            'optimize:clear',
            'queue:failed',
            'queue:flush',
            'queue:retry',
            'queue:work',
            'schedule:list',
            'schedule:run',
            'serve',
            'make:controller',
            'make:job',
            'make:model',
            'make:middleware',
            'make:request',
            'make:policy',
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
            'route:cache',
            'route:clear',
            'config:cache',
            'config:clear',
            'config:get',
            'config:list',
            'container:list',
            'vendor:publish',
            'optimize',
            'optimize:clear',
            'queue:failed',
            'queue:flush',
            'queue:retry',
            'queue:work',
            'schedule:list',
            'schedule:run',
            'serve',
            'make:controller',
            'make:job',
            'make:model',
            'make:middleware',
            'make:request',
            'make:policy',
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
        $this->assertSame(['configs'], $console->getAliasesFor('config:list'));
        $this->assertSame(['schedules'], $console->getAliasesFor('schedule:list'));
    }

    public function test_config_list_redacts_sensitive_values(): void
    {
        Config::reset();

        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $app->make(Config::class);
        Config::set('app.name', 'Annabel');
        Config::set('database.password', 'secret');
        Config::set('session.encryption.key', 'base64-key');

        $console = new Console();
        $console->setApplication($app);

        $exitCode = null;
        $output = $this->captureOutput(function () use ($console, &$exitCode) {
            $exitCode = $console->run(['annabel', 'config:list']);
        });

        $this->assertSame(ExitCode::SUCCESS, $exitCode);
        $this->assertStringContainsString('app.name = Annabel', $output);
        $this->assertStringContainsString('database.password = ********', $output);
        $this->assertStringContainsString('session.encryption.key = ********', $output);
        $this->assertStringNotContainsString('secret', $output);
        $this->assertStringNotContainsString('base64-key', $output);

        Config::reset();
    }

    public function test_config_list_can_show_secrets_explicitly(): void
    {
        Config::reset();

        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $app->make(Config::class);
        Config::set('database.password', 'secret');

        $console = new Console();
        $console->setApplication($app);

        $exitCode = null;
        $output = $this->captureOutput(function () use ($console, &$exitCode) {
            $exitCode = $console->run(['annabel', 'config:list', '--show-secrets']);
        });

        $this->assertSame(ExitCode::SUCCESS, $exitCode);
        $this->assertStringContainsString('database.password = secret', $output);

        Config::reset();
    }

    public function test_schedule_list_displays_registered_tasks(): void
    {
        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $schedule = new Schedule();
        $schedule->call(fn (): null => null, 'cleanup')
            ->dailyAt('03:15')
            ->withoutOverlapping(10);
        $app->getContainer()->instance(Schedule::class, $schedule);

        $console = new Console();
        $console->setApplication($app);

        $exitCode = null;
        $output = $this->captureOutput(function () use ($console, &$exitCode) {
            $exitCode = $console->run(['annabel', 'schedule:list']);
        });

        $this->assertSame(ExitCode::SUCCESS, $exitCode);
        $this->assertStringContainsString('Scheduled tasks:', $output);
        $this->assertStringContainsString('15 3 * * *', $output);
        $this->assertStringContainsString('cleanup', $output);
        $this->assertStringContainsString('locked 600s', $output);
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
        $this->captureOutput(fn () => $console->run([
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
            $output->content(),
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

    public function test_make_commands_generate_application_classes(): void
    {
        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $output = new BufferedOutput();
        $console = new Console($output);
        $console->setApplication($app);

        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'make:controller', 'Admin/User']));
        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'make:model', 'User']));
        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'make:middleware', 'Authenticate']));
        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'make:request', 'StoreUser']));
        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'make:policy', 'Post']));

        $controller = $basePath . '/app/Controllers/Admin/UserController.php';
        $model = $basePath . '/app/Models/User.php';
        $middleware = $basePath . '/app/Middleware/Authenticate.php';
        $request = $basePath . '/app/Http/Requests/StoreUserRequest.php';
        $policy = $basePath . '/app/Policies/PostPolicy.php';

        $this->paths[] = $controller;
        $this->paths[] = $model;
        $this->paths[] = $middleware;
        $this->paths[] = $request;
        $this->paths[] = $policy;
        $this->paths[] = $basePath . '/app/Controllers/Admin';
        $this->paths[] = $basePath . '/app/Controllers';
        $this->paths[] = $basePath . '/app/Models';
        $this->paths[] = $basePath . '/app/Middleware';
        $this->paths[] = $basePath . '/app/Http/Requests';
        $this->paths[] = $basePath . '/app/Http';
        $this->paths[] = $basePath . '/app/Policies';
        $this->paths[] = $basePath . '/app';

        $this->assertFileExists($controller);
        $this->assertFileExists($model);
        $this->assertFileExists($middleware);
        $this->assertFileExists($request);
        $this->assertFileExists($policy);
        $this->assertStringContainsString('namespace App\\Controllers\\Admin;', file_get_contents($controller));
        $this->assertStringContainsString('class UserController', file_get_contents($controller));
        $this->assertStringContainsString('class User extends Model', file_get_contents($model));
        $this->assertStringContainsString('class StoreUserRequest', file_get_contents($request));
        $this->assertStringContainsString('class PostPolicy', file_get_contents($policy));
    }

    public function test_make_commands_do_not_overwrite_without_force(): void
    {
        $basePath = $this->directory('annabel-console-app-');
        $app = new Application($basePath, null, false);
        $output = new BufferedOutput();
        $console = new Console($output);
        $console->setApplication($app);

        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'make:controller', 'Home']));
        $this->assertSame(ExitCode::FAILURE, $console->run(['annabel', 'make:controller', 'Home']));
        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'make:controller', 'Home', '--force']));

        $this->paths[] = $basePath . '/app/Controllers/HomeController.php';
        $this->paths[] = $basePath . '/app/Controllers';
        $this->paths[] = $basePath . '/app';

        $this->assertStringContainsString('File already exists', $output->content());
    }

    public function test_failed_queue_commands_manage_database_jobs(): void
    {
        $connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $connection->statement('CREATE TABLE jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT NOT NULL, payload TEXT NOT NULL, attempts INTEGER NOT NULL, max_attempts INTEGER NOT NULL, reserved_at INTEGER NULL, available_at INTEGER NOT NULL, created_at INTEGER NOT NULL)');
        $connection->statement('CREATE TABLE failed_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, connection TEXT NOT NULL, queue TEXT NOT NULL, payload TEXT NOT NULL, exception TEXT NULL, failed_at INTEGER NOT NULL)');
        $payload = (new JobSerializer())->serialize(new TestFailedCliJob());
        $failedId = (string) $connection->table('failed_jobs')->insertGetId([
            'connection' => 'database',
            'queue' => 'emails',
            'payload' => $payload,
            'exception' => 'RuntimeException: Expected failure.',
            'failed_at' => time(),
        ]);
        $manager = new QueueManager([
            'default' => 'database',
            'connections' => [
                'database' => [
                    'driver' => 'database',
                    'connection' => $connection,
                ],
            ],
        ]);
        $app = new Application($this->directory('annabel-console-app-'), null, false);
        $app->getContainer()->instance(QueueManager::class, $manager);
        $output = new BufferedOutput();
        $console = new Console($output);
        $console->setApplication($app);

        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'queue:failed']));
        $this->assertStringContainsString($failedId . '  database  emails', $output->content());
        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'queue:retry', $failedId]));
        $this->assertCount(1, $connection->table('jobs')->get());
        $this->assertCount(0, $connection->table('failed_jobs')->get());

        $connection->table('failed_jobs')->insert([
            'connection' => 'database',
            'queue' => 'emails',
            'payload' => $payload,
            'exception' => null,
            'failed_at' => time(),
        ]);

        $this->assertSame(ExitCode::SUCCESS, $console->run(['annabel', 'queue:flush']));
        $this->assertCount(0, $connection->table('failed_jobs')->get());
        $this->assertStringContainsString('Deleted 1 failed job(s).', $output->content());
    }

    public function test_queue_worker_can_stop_when_queue_is_empty(): void
    {
        $app = new Application($this->directory('annabel-console-app-'), null, false);
        $app->getContainer()->instance(Worker::class, new Worker(new EmptyTestQueue()));
        $output = new BufferedOutput();
        $console = new Console($output);
        $console->setApplication($app);

        $this->assertSame(
            ExitCode::SUCCESS,
            $console->run(['annabel', 'queue:work', '--stop-when-empty', '--sleep=0']),
        );
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
    public function __construct(public string $value)
    {
    }
}

class TestCliCommand extends Command
{
    public function __construct(private TestCliDependency $dependency)
    {
    }

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
            $input->option('name', ''),
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

class TestFailedCliJob implements JobInterface
{
    public function handle(): void
    {
    }
}

class EmptyTestQueue implements WorkableQueueInterface
{
    public function push(JobInterface $job, ?string $queue = null): JobResult
    {
        throw new \LogicException('Not used by this test.');
    }

    public function pop(?string $queue = null): ?QueuedJob
    {
        return null;
    }
}
