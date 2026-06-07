<?php

namespace Codemonster\Config;

class Config
{
    /** @var array<string, mixed> */
    protected static array $items = [];

    public static function load(string $path): void
    {
        $files = glob(rtrim($path, '/') . '/*.php');

        if ($files === false) {
            throw new \RuntimeException("Unable to scan config directory: {$path}");
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');

            static::$items[$key] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = static::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &static::$items;

        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }

            $ref = &$ref[$segment];
        }

        $ref = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return static::$items;
    }

    public static function reset(): void
    {
        static::$items = [];
    }
}
