<?php

namespace Codemonster\Annabel\Console;

use Codemonster\Annabel\Console\Contracts\OutputInterface;

class BufferedOutput implements OutputInterface
{
    protected string $buffer = '';

    public function write(string $text): void
    {
        $this->buffer .= $text;
    }

    public function writeln(string $line = ''): void
    {
        $this->write($line . PHP_EOL);
    }

    public function content(): string
    {
        return $this->buffer;
    }
}
