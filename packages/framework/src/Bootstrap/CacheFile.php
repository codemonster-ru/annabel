<?php

namespace Codemonster\Annabel\Bootstrap;

final class CacheFile
{
    /**
     * @param array<mixed> $data
     */
    public static function write(string $path, array $data): void
    {
        self::assertExportable($data);

        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException("Unable to create cache directory [{$directory}].");
        }

        $temporary = tempnam($directory, 'annabel-cache-');
        if ($temporary === false) {
            throw new \RuntimeException("Unable to create temporary cache file in [{$directory}].");
        }

        $contents = "<?php\n\nreturn " . var_export($data, true) . ";\n";

        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
                throw new \RuntimeException("Unable to write cache file [{$path}].");
            }

            if (!rename($temporary, $path)) {
                throw new \RuntimeException("Unable to replace cache file [{$path}].");
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public static function clear(string $path): bool
    {
        return !is_file($path) || unlink($path);
    }

    private static function assertExportable(mixed $value, string $path = 'root'): void
    {
        if ($value === null || is_scalar($value)) {
            return;
        }

        if (!is_array($value)) {
            throw new \RuntimeException("Cache value [{$path}] must contain only arrays and scalar values.");
        }

        foreach ($value as $key => $item) {
            self::assertExportable($item, $path . '.' . $key);
        }
    }
}
