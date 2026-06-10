<?php

namespace Codemonster\Filesystem\Contracts;

interface FilesystemInterface
{
    public function exists(string $path): bool;

    public function missing(string $path): bool;

    public function get(string $path): string;

    public function put(string $path, string $contents): void;

    public function append(string $path, string $contents): void;

    public function prepend(string $path, string $contents): void;

    public function delete(string $path): void;

    public function copy(string $from, string $to): void;

    public function move(string $from, string $to): void;

    public function size(string $path): int;

    public function lastModified(string $path): int;

    public function mimeType(string $path): string;

    public function makeDirectory(string $path): void;

    public function deleteDirectory(string $path): void;

    /**
     * @return list<string>
     */
    public function files(string $directory = ''): array;

    /**
     * @return list<string>
     */
    public function directories(string $directory = ''): array;

    public function path(string $path): string;

    public function url(string $path): string;
}
