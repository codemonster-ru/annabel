<?php

namespace Codemonster\Dumper;

use InvalidArgumentException;

class Dumper
{
    public static function dump(mixed $value, ?string $mode = null): void
    {
        $mode ??= (PHP_SAPI === 'cli' ? 'cli' : 'html');

        match ($mode) {
            'cli' => static::dumpCli($value),
            'html' => static::dumpHtml($value),
            default => throw new InvalidArgumentException("Invalid dump mode: {$mode}")
        };
    }

    public static function dd(...$values): never
    {
        $mode = null;

        if (count($values) > 1) {
            $last = end($values);

            if (is_string($last) && in_array($last, ['cli', 'html'], true)) {
                $mode = array_pop($values);
            }
        }

        foreach ($values as $value) {
            static::dump($value, $mode);
        }

        static::terminate();
    }

    protected static function terminate(): never
    {
        exit(1);
    }

    protected static function dumpCli(mixed $value): void
    {
        echo "\033[33m";
        var_dump($value);
        echo "\033[0m";
    }

    protected static function dumpHtml(mixed $value): void
    {
        echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:10px;border-radius:6px;">';
        echo htmlspecialchars(print_r($value, true));
        echo '</pre>';
    }
}
