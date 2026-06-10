<?php

namespace Codemonster\Annabel\Tests\Assets;

use Codemonster\Annabel\Assets\Vite;
use PHPUnit\Framework\TestCase;

class ViteTest extends TestCase
{
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_render_build_assets_from_manifest(): void
    {
        $basePath = $this->directory();
        $build = $this->directory($basePath . '/public/build');
        file_put_contents($build . '/manifest.json', json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
                'css' => ['assets/app-123.css'],
            ],
        ], JSON_THROW_ON_ERROR));
        $this->paths[] = $build . '/manifest.json';

        $html = (new Vite($basePath))->render('resources/js/app.js');

        self::assertStringContainsString('<link rel="stylesheet" href="/build/assets/app-123.css">', $html);
        self::assertStringContainsString('<script type="module" src="/build/assets/app-123.js"></script>', $html);
    }

    public function test_render_hot_assets(): void
    {
        $basePath = $this->directory();
        $public = $this->directory($basePath . '/public');
        file_put_contents($public . '/hot', 'http://localhost:5173');
        $this->paths[] = $public . '/hot';

        $html = (new Vite($basePath))->render('resources/js/app.js');

        self::assertStringContainsString('http://localhost:5173/@vite/client', $html);
        self::assertStringContainsString('http://localhost:5173/resources/js/app.js', $html);
    }

    private function directory(?string $path = null): string
    {
        $path ??= sys_get_temp_dir() . '/annabel-vite-' . bin2hex(random_bytes(6));

        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }

        $this->paths[] = $path;

        return $path;
    }
}
