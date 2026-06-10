<?php

namespace Codemonster\Env;

interface LoaderInterface
{
    public function resetLoaded(): void;

    public function loadFile(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
    ): void;

    public function safeLoadFile(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
    ): bool;

    public function loadString(
        string $content,
        ?string $encoding = null,
        bool $strictResolve = false,
        ?int $maxBytes = null,
    ): void;

    public function safeLoadString(
        string $content,
        ?string $encoding = null,
        bool $strictResolve = false,
        ?int $maxBytes = null,
    ): bool;

    /** @param iterable<mixed> $paths */
    public function loadFiles(
        iterable $paths,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
        bool $shortCircuit = true,
        ?int $globFlags = null,
    ): void;

    /** @param iterable<mixed> $paths */
    public function safeLoadFiles(
        iterable $paths,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
        bool $shortCircuit = true,
        ?int $globFlags = null,
    ): bool;
}
