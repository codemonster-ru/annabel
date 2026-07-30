<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\LoggingServiceProvider;
use Codemonster\Config\Config;
use Codemonster\DateTime\FrozenClock;
use Codemonster\Logging\LoggerManager;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class LoggingServiceProviderTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();

        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_logging_services_are_registered(): void
    {
        $path = $this->file();
        $app = $this->app([
            'logging.default' => 'file',
            'logging.channels.file.driver' => 'file',
            'logging.channels.file.path' => $path,
        ]);
        $app->getContainer()->instance(
            ClockInterface::class,
            new FrozenClock(new \DateTimeImmutable('2026-07-31 12:30:00 UTC')),
        );

        $logger = $app->make(LoggerInterface::class);
        $logger->info('Hello {name}', ['name' => 'Annabel']);

        self::assertInstanceOf(LoggerManager::class, $app->make(LoggerManager::class));
        self::assertInstanceOf(LoggerInterface::class, $app->make('logger'));
        $contents = (string) file_get_contents($path);

        self::assertStringStartsWith('[2026-07-31 12:30:00]', $contents);
        self::assertStringContainsString('Hello Annabel', $contents);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function app(array $configuration): Application
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        (new CoreServiceProvider($app))->register();

        config($configuration);

        (new LoggingServiceProvider($app))->register();

        return $app;
    }

    private function file(): string
    {
        $directory = sys_get_temp_dir() . '/annabel-framework-logging-' . bin2hex(random_bytes(6));
        $path = $directory . '/app.log';
        $this->paths[] = $path;
        $this->paths[] = $directory;

        return $path;
    }
}
