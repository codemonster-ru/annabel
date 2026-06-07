<?php

namespace Codemonster\Annabel\Console;

use Codemonster\Annabel\Console\Contracts\OutputInterface;

class StreamOutput implements OutputInterface
{
    /** @var resource|null */
    protected mixed $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct(mixed $stream = null)
    {
        $this->stream = $stream;

        if ($this->stream !== null && !is_resource($this->stream)) {
            throw new \InvalidArgumentException('Console output stream must be a resource.');
        }
    }

    public function write(string $text): void
    {
        if ($this->stream === null) {
            echo $text;

            return;
        }

        fwrite($this->stream, $text);
    }

    public function writeln(string $line = ''): void
    {
        $this->write($line . PHP_EOL);
    }
}
