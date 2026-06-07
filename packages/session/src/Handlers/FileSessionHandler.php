<?php

namespace Codemonster\Session\Handlers;

use SessionHandlerInterface;

/** @api */
class FileSessionHandler implements SessionHandlerInterface
{
    public function __construct(protected string $path)
    {
        if (file_exists($path) && !is_dir($path)) {
            throw new \RuntimeException("Session path exists and is not a directory: {$path}");
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                throw new \RuntimeException("Failed to create session directory: {$path}");
            }
        }
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $file = "{$this->path}/sess_{$id}";

        if (!file_exists($file)) {
            return '';
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return '';
        }

        $contents = '';

        if (flock($handle, LOCK_SH)) {
            $read = stream_get_contents($handle);
            $contents = $read === false ? '' : $read;
            flock($handle, LOCK_UN);
        }

        fclose($handle);

        return $contents;
    }

    public function write(string $id, string $data): bool
    {
        $file = "{$this->path}/sess_{$id}";

        $tmp = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (file_put_contents($tmp, $data, LOCK_EX) === false) {
            return false;
        }

        if (@rename($tmp, $file)) {
            return true;
        }

        @unlink($tmp);

        return file_put_contents($file, $data, LOCK_EX) !== false;
    }

    public function destroy(string $id): bool
    {
        $file = "{$this->path}/sess_{$id}";

        return file_exists($file) ? unlink($file) : true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $count = 0;

        $files = glob("{$this->path}/sess_*");

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            $modifiedAt = filemtime($file);
            if ($modifiedAt !== false && $modifiedAt + $max_lifetime < time()) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
