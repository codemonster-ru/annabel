<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Assets\Vite;
use Codemonster\Annabel\Container;

class AssetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/assets.php' => $this->app()->getBasePath() . '/config/assets.php',
        ], ['config', 'assets']);

        $this->app()->singleton(Vite::class, fn (): Vite => new Vite(
            $this->app()->getBasePath(),
            $this->assetsConfig(),
        ));
        $this->app()->singleton('vite', fn (Container $app): Vite => $app->make(Vite::class));
    }

    /** @return array<string, mixed> */
    private function assetsConfig(): array
    {
        $config = config('assets.vite', []);

        if (!is_array($config)) {
            throw new \RuntimeException('Vite asset config must be an array.');
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
