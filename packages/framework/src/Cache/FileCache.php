<?php

namespace Codemonster\Annabel\Cache;

use DateInterval;

class FileCache extends ArrayCache
{
    public function __construct(protected string $path) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);
        $payload = $this->read($key);

        if ($payload === null) {
            return $default;
        }

        $expiresAt = $payload['expires_at'] ?? null;

        if (is_int($expiresAt) && $expiresAt <= time()) {
            $this->delete($key);

            return $default;
        }

        return $payload['value'];
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->assertValidKey($key);
        $this->ensureDirectory();

        $payload = serialize([
            'value' => $value,
            'expires_at' => $this->expiresAt($ttl),
        ]);

        return file_put_contents($this->file($key), $payload, LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey($key);
        $file = $this->file($key);

        if (is_file($file)) {
            return unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        if (!is_dir($this->path)) {
            return true;
        }

        $files = glob(rtrim($this->path, '/') . '/*.cache');

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                return false;
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertValidKey($key);
        $payload = $this->read($key);

        if ($payload === null) {
            return false;
        }

        $expiresAt = $payload['expires_at'] ?? null;

        if (is_int($expiresAt) && $expiresAt <= time()) {
            $this->delete($key);

            return false;
        }

        return true;
    }

    /**
     * @return array{value: mixed, expires_at?: int|null}|null
     */
    protected function read(string $key): ?array
    {
        $file = $this->file($key);

        if (!is_file($file)) {
            return null;
        }

        $payload = file_get_contents($file);

        if ($payload === false) {
            return null;
        }

        $data = @unserialize($payload);

        if (!is_array($data) || !array_key_exists('value', $data)) {
            return null;
        }

        $expiresAt = $data['expires_at'] ?? null;
        if ($expiresAt !== null && !is_int($expiresAt)) {
            return null;
        }

        return [
            'value' => $data['value'],
            'expires_at' => $expiresAt,
        ];
    }

    protected function file(string $key): string
    {
        return rtrim($this->path, '/') . '/' . sha1($key) . '.cache';
    }

    protected function ensureDirectory(): void
    {
        if (!is_dir($this->path) && !mkdir($this->path, 0770, true) && !is_dir($this->path)) {
            throw new \RuntimeException("Unable to create cache directory: {$this->path}");
        }
    }
}
