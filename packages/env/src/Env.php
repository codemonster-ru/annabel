<?php

namespace Codemonster\Env;

class Env
{
    private static ?LoaderInterface $defaultLoader = null;
    private static ?ParserInterface $defaultParser = null;

    public static function setDefaultLoader(?LoaderInterface $loader): void
    {
        self::$defaultLoader = $loader;
    }

    public static function setDefaultParser(?ParserInterface $parser): void
    {
        self::$defaultParser = $parser;
        self::$defaultLoader = null;
    }

    public static function reset(): void
    {
        self::getDefaultLoaderInternal()->resetLoaded();
        self::$defaultParser = null;
    }

    public static function load(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
    ): void {
        self::getDefaultLoaderInternal()->loadFile($path, $encoding, $maxBytes, $strictResolve);
    }

    public static function loadString(
        string $content,
        ?string $encoding = null,
        bool $strictResolve = false,
        ?int $maxBytes = null,
    ): void {
        self::getDefaultLoaderInternal()->loadString($content, $encoding, $strictResolve, $maxBytes);
    }

    public static function safeLoad(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
    ): bool {
        return self::getDefaultLoaderInternal()->safeLoadFile($path, $encoding, $maxBytes, $strictResolve);
    }

    public static function safeLoadString(
        string $content,
        ?string $encoding = null,
        bool $strictResolve = false,
        ?int $maxBytes = null,
    ): bool {
        return self::getDefaultLoaderInternal()->safeLoadString(
            $content,
            $encoding,
            $strictResolve,
            $maxBytes,
        );
    }

    /** @param iterable<mixed> $paths */
    public static function loadFiles(
        iterable $paths,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
        bool $shortCircuit = true,
        ?int $globFlags = null,
    ): void {
        self::getDefaultLoaderInternal()->loadFiles(
            $paths,
            $encoding,
            $maxBytes,
            $strictResolve,
            $shortCircuit,
            $globFlags,
        );
    }

    /** @param iterable<mixed> $paths */
    public static function safeLoadFiles(
        iterable $paths,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
        bool $shortCircuit = true,
        ?int $globFlags = null,
    ): bool {
        return self::getDefaultLoaderInternal()->safeLoadFiles(
            $paths,
            $encoding,
            $maxBytes,
            $strictResolve,
            $shortCircuit,
            $globFlags,
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $value = getenv($key);

        return $value === false ? $default : $value;
    }

    public static function getCast(string $key, mixed $default = null, bool $cast = false): mixed
    {
        if (!$cast) {
            return self::get($key, $default);
        }

        if (array_key_exists($key, $_ENV)) {
            return self::castValue($_ENV[$key]);
        }

        if (array_key_exists($key, $_SERVER)) {
            return self::castValue($_SERVER[$key]);
        }

        $value = getenv($key);

        return $value === false ? $default : self::castValue($value);
    }

    /** @return list<array{string, string|null, list<int>}> */
    public static function parse(
        string $content,
        ?string $encoding = null,
        bool $strict = false,
    ): array {
        $entries = EnvParser::parseStringRaw($content, $encoding);

        if ($strict) {
            $seen = [];

            foreach ($entries as $entry) {
                $name = $entry[0];

                if (isset($seen[$name])) {
                    throw new Exceptions\InvalidFileException("Duplicate entry for {$name}.");
                }

                $seen[$name] = true;
            }
        }

        return $entries;
    }

    /** @return array<string, string|null> */
    public static function parseToArray(
        string $content,
        ?string $encoding = null,
        bool $strict = false,
    ): array {
        $entries = EnvParser::parseStringRaw($content, $encoding);
        $parsed = [];

        foreach ($entries as $entry) {
            $name = $entry[0];

            if ($strict && array_key_exists($name, $parsed)) {
                throw new Exceptions\InvalidFileException("Duplicate entry for {$name}.");
            }

            $parsed[$name] = $entry[1];
        }

        return $parsed;
    }

    public static function getDefaultLoader(): LoaderInterface
    {
        return self::getDefaultLoaderInternal();
    }

    public static function getDefaultParser(): ?ParserInterface
    {
        return self::$defaultParser;
    }

    protected static function getDefaultLoaderInternal(): LoaderInterface
    {
        if (self::$defaultLoader === null) {
            self::$defaultLoader = new EnvLoader(self::$defaultParser);
        }

        return self::$defaultLoader;
    }

    private static function castValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        $lower = strtolower($trimmed);

        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => self::stripQuotes($trimmed) ?? $value,
        };
    }

    private static function stripQuotes(string $value): ?string
    {
        if (strlen($value) < 2) {
            return null;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' || $first === "'") && $last === $first) {
            return substr($value, 1, -1);
        }

        return null;
    }
}
