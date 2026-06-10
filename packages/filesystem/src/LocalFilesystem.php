<?php

namespace Codemonster\Filesystem;

use Codemonster\Filesystem\Contracts\FilesystemInterface;
use Codemonster\Filesystem\Exceptions\FilesystemException;

class LocalFilesystem implements FilesystemInterface
{
    protected string $root;
    protected ?string $baseUrl;

    public function __construct(string $root, ?string $baseUrl = null)
    {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
        $this->baseUrl = $baseUrl !== null ? rtrim($baseUrl, '/') : null;

        if ($this->root === '') {
            throw new FilesystemException('Filesystem root cannot be empty.');
        }

        if (!is_dir($this->root) && !mkdir($this->root, 0770, true) && !is_dir($this->root)) {
            throw new FilesystemException("Unable to create filesystem root [{$this->root}].");
        }
    }

    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    public function get(string $path): string
    {
        $fullPath = $this->path($path);

        if (!is_file($fullPath) || !is_readable($fullPath)) {
            throw new FilesystemException("File [{$path}] does not exist or is not readable.");
        }

        $contents = file_get_contents($fullPath);
        if ($contents === false) {
            throw new FilesystemException("Unable to read file [{$path}].");
        }

        return $contents;
    }

    public function put(string $path, string $contents): void
    {
        $fullPath = $this->path($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new FilesystemException("Unable to create directory [{$directory}].");
        }

        if (file_put_contents($fullPath, $contents, LOCK_EX) === false) {
            throw new FilesystemException("Unable to write file [{$path}].");
        }
    }

    public function append(string $path, string $contents): void
    {
        $this->writeWithFlags($path, $contents, FILE_APPEND | LOCK_EX);
    }

    public function prepend(string $path, string $contents): void
    {
        $existing = $this->exists($path) ? $this->get($path) : '';
        $this->put($path, $contents . $existing);
    }

    public function delete(string $path): void
    {
        $fullPath = $this->path($path);

        if (is_file($fullPath) && !unlink($fullPath)) {
            throw new FilesystemException("Unable to delete file [{$path}].");
        }
    }

    public function copy(string $from, string $to): void
    {
        $source = $this->path($from);
        $target = $this->path($to);
        $directory = dirname($target);

        if (!is_file($source)) {
            throw new FilesystemException("File [{$from}] does not exist.");
        }

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new FilesystemException("Unable to create directory [{$directory}].");
        }

        if (!copy($source, $target)) {
            throw new FilesystemException("Unable to copy file [{$from}] to [{$to}].");
        }
    }

    public function move(string $from, string $to): void
    {
        $source = $this->path($from);
        $target = $this->path($to);
        $directory = dirname($target);

        if (!is_file($source)) {
            throw new FilesystemException("File [{$from}] does not exist.");
        }

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new FilesystemException("Unable to create directory [{$directory}].");
        }

        if (!rename($source, $target)) {
            throw new FilesystemException("Unable to move file [{$from}] to [{$to}].");
        }
    }

    public function size(string $path): int
    {
        $size = filesize($this->path($path));

        if ($size === false) {
            throw new FilesystemException("Unable to read file size [{$path}].");
        }

        return $size;
    }

    public function lastModified(string $path): int
    {
        $time = filemtime($this->path($path));

        if ($time === false) {
            throw new FilesystemException("Unable to read last modified time [{$path}].");
        }

        return $time;
    }

    public function mimeType(string $path): string
    {
        $type = function_exists('mime_content_type') ? mime_content_type($this->path($path)) : false;

        return is_string($type) && $type !== '' ? $type : 'application/octet-stream';
    }

    public function makeDirectory(string $path): void
    {
        $directory = $this->path($path);

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new FilesystemException("Unable to create directory [{$path}].");
        }
    }

    public function deleteDirectory(string $path): void
    {
        $directory = $this->path($path);

        if (!is_dir($directory)) {
            return;
        }

        $this->deleteDirectoryContents($directory);

        if ($directory !== $this->root && !rmdir($directory)) {
            throw new FilesystemException("Unable to delete directory [{$path}].");
        }
    }

    public function files(string $directory = ''): array
    {
        return $this->listPaths($directory, true);
    }

    public function directories(string $directory = ''): array
    {
        return $this->listPaths($directory, false);
    }

    public function path(string $path): string
    {
        $normalized = $this->normalizePath($path);

        return $normalized === '' ? $this->root : $this->root . DIRECTORY_SEPARATOR . $normalized;
    }

    public function url(string $path): string
    {
        $normalized = str_replace(DIRECTORY_SEPARATOR, '/', $this->normalizePath($path));

        if ($this->baseUrl === null) {
            return '/' . $normalized;
        }

        return $normalized === '' ? $this->baseUrl : $this->baseUrl . '/' . $normalized;
    }

    private function writeWithFlags(string $path, string $contents, int $flags): void
    {
        $fullPath = $this->path($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new FilesystemException("Unable to create directory [{$directory}].");
        }

        if (file_put_contents($fullPath, $contents, $flags) === false) {
            throw new FilesystemException("Unable to write file [{$path}].");
        }
    }

    /**
     * @return list<string>
     */
    private function listPaths(string $directory, bool $files): array
    {
        $root = $this->path($directory);

        if (!is_dir($root)) {
            return [];
        }

        $paths = [];
        $entries = scandir($root);

        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $root . DIRECTORY_SEPARATOR . $entry;
            if ($files ? is_file($fullPath) : is_dir($fullPath)) {
                $paths[] = trim($this->relativePath($fullPath), '/');
            }
        }

        sort($paths);

        return $paths;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace(['\\', "\0"], ['/', ''], $path);
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw new FilesystemException('Filesystem paths cannot escape the configured root.');
            }

            $segments[] = $segment;
        }

        return implode(DIRECTORY_SEPARATOR, $segments);
    }

    private function deleteDirectoryContents(string $directory): void
    {
        $entries = scandir($directory);

        if ($entries === false) {
            throw new FilesystemException("Unable to read directory [{$directory}].");
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $this->deleteDirectoryContents($path);

                if (!rmdir($path)) {
                    throw new FilesystemException("Unable to delete directory [{$path}].");
                }

                continue;
            }

            if (is_file($path) && !unlink($path)) {
                throw new FilesystemException("Unable to delete file [{$path}].");
            }
        }
    }

    private function relativePath(string $path): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', ltrim(substr($path, strlen($this->root)), DIRECTORY_SEPARATOR));
    }
}
