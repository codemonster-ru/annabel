<?php

namespace Codemonster\Annabel\Bootstrap;

final class ConfigCache
{
    public static function path(string $basePath): string
    {
        return $basePath . '/bootstrap/cache/config.php';
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(string $basePath): array
    {
        $directory = $basePath . '/config';
        $files = glob($directory . '/*.php');

        if ($files === false) {
            throw new \RuntimeException("Unable to scan config directory [{$directory}].");
        }

        sort($files);
        $config = [];

        foreach ($files as $file) {
            $config[basename($file, '.php')] = require $file;
        }

        return $config;
    }

    public static function write(string $basePath): int
    {
        $config = self::build($basePath);
        CacheFile::write(self::path($basePath), $config);

        return count($config);
    }

    public static function clear(string $basePath): bool
    {
        return CacheFile::clear(self::path($basePath));
    }
}
