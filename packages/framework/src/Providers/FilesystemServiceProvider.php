<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Filesystem\Contracts\FilesystemInterface;
use Codemonster\Filesystem\FilesystemManager;

class FilesystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/filesystem.php' => $this->app()->getBasePath() . '/config/filesystem.php',
        ], ['config', 'filesystem']);

        $this->app()->singleton(FilesystemManager::class, fn (): FilesystemManager => new FilesystemManager(
            $this->filesystemConfig(),
        ));
        $this->app()->singleton('filesystem', fn (Container $app): FilesystemManager => $app->make(FilesystemManager::class));
        $this->app()->singleton(FilesystemInterface::class, fn (Container $app): FilesystemInterface => $app
            ->make(FilesystemManager::class)
            ->disk());
        $this->app()->singleton('storage', fn (Container $app): FilesystemInterface => $app->make(FilesystemInterface::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function filesystemConfig(): array
    {
        $config = config('filesystem', [
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => base_path('storage/app'),
                ],
            ],
        ]);

        if (!is_array($config)) {
            throw new \RuntimeException('Filesystem config must be an array.');
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
