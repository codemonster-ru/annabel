<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Logging\LoggerManager;
use Psr\Log\LoggerInterface;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(LoggerManager::class, fn (): LoggerManager => new LoggerManager(
            $this->loggingConfig(),
        ));
        $this->app()->singleton('logger.manager', fn (Container $app): LoggerManager => $app->make(LoggerManager::class));
        $this->app()->singleton(LoggerInterface::class, fn (Container $app): LoggerInterface => $app
            ->make(LoggerManager::class)
            ->channel());
        $this->app()->singleton('logger', fn (Container $app): LoggerInterface => $app->make(LoggerInterface::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function loggingConfig(): array
    {
        $config = config('logging', [
            'default' => 'null',
            'channels' => [
                'null' => [
                    'driver' => 'null',
                ],
            ],
        ]);

        if (!is_array($config)) {
            throw new \RuntimeException('Logging config must be an array.');
        }

        $normalized = [];
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
