<?php

namespace Codemonster\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /** @var resource|null */
    protected $resource;

    public function __construct(string $contents = '')
    {
        $resource = fopen('php://temp', 'r+');

        if ($resource === false) {
            throw new \RuntimeException('Unable to open temporary stream.');
        }

        $this->resource = $resource;

        if ($contents !== '') {
            fwrite($this->resource, $contents);
            rewind($this->resource);
        }
    }

    public function __toString(): string
    {
        try {
            $this->rewind();

            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }

        $this->resource = null;
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $stats = fstat($this->resource);

        return $stats === false ? null : $stats['size'];
    }

    public function tell(): int
    {
        $resource = $this->attachedResource();
        $position = ftell($resource);

        if ($position === false) {
            throw new \RuntimeException('Unable to determine stream position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        return !is_resource($this->resource) || feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return is_resource($this->resource) && (bool) ($this->getMetadata('seekable') ?? false);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $resource = $this->attachedResource();

        if (!$this->isSeekable() || fseek($resource, $offset, $whence) !== 0) {
            throw new \RuntimeException('Unable to seek stream.');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        $mode = $this->getMetadata('mode');

        return is_string($mode) && strpbrk($mode, 'waxc+') !== false;
    }

    public function write(string $string): int
    {
        $resource = $this->attachedResource();

        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable.');
        }

        $written = fwrite($resource, $string);

        if ($written === false) {
            throw new \RuntimeException('Unable to write to stream.');
        }

        return $written;
    }

    public function isReadable(): bool
    {
        $mode = $this->getMetadata('mode');

        return is_string($mode) && strpbrk($mode, 'r+') !== false;
    }

    public function read(int $length): string
    {
        if ($length <= 0) {
            throw new \RuntimeException('Read length must be greater than zero.');
        }

        $resource = $this->attachedResource();

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable.');
        }

        $data = fread($resource, $length);

        if ($data === false) {
            throw new \RuntimeException('Unable to read from stream.');
        }

        return $data;
    }

    public function getContents(): string
    {
        $resource = $this->attachedResource();
        $contents = stream_get_contents($resource);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents.');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if (!is_resource($this->resource)) {
            return $key === null ? [] : null;
        }

        $metadata = stream_get_meta_data($this->resource);

        return $key === null ? $metadata : ($metadata[$key] ?? null);
    }

    /** @return resource */
    protected function attachedResource()
    {
        if (!is_resource($this->resource)) {
            throw new \RuntimeException('Stream is detached.');
        }

        return $this->resource;
    }
}
