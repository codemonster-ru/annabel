<?php

namespace Codemonster\Session\Handlers;

use SessionHandlerInterface;

class ArraySessionHandler implements SessionHandlerInterface
{
    /** @var array<string, string> */
    protected array $storage = [];

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
        return $this->storage[$id] ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $this->storage[$id] = $data;

        return true;
    }

    public function destroy(string $id): bool
    {
        unset($this->storage[$id]);

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }
}
