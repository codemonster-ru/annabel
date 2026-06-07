<?php

namespace Codemonster\Annabel\Console\Contracts;

interface OutputInterface
{
    public function write(string $text): void;

    public function writeln(string $line = ''): void;
}
