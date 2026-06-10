<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\SessionServiceProvider;
use Codemonster\Config\Config;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;

class SessionServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    public function test_array_driver_registers_session_store(): void
    {
        $app = $this->app([
            'session.driver' => 'array',
        ]);

        self::assertInstanceOf(Store::class, $app->make('session'));
    }

    public function test_file_driver_creates_configured_directory(): void
    {
        $path = sys_get_temp_dir() . '/annabel-session-' . bin2hex(random_bytes(6));
        $app = $this->app([
            'session.driver' => 'file',
            'session.path' => $path,
        ]);

        try {
            self::assertInstanceOf(Store::class, $app->make('session'));
            self::assertDirectoryExists($path);
        } finally {
            @rmdir($path);
        }
    }

    public function test_unknown_driver_is_rejected(): void
    {
        $app = $this->app([
            'session.driver' => 'unknown',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported session driver: unknown');

        $app->make('session');
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function app(array $configuration): Application
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        $core = new CoreServiceProvider($app);
        $core->register();

        config($configuration);

        $provider = new SessionServiceProvider($app);
        $provider->register();

        return $app;
    }
}
