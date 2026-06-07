<?php

namespace Codemonster\Annabel\Console\Contracts;

interface InputInterface
{
    public function command(): string;

    /**
     * @return list<string>
     */
    public function arguments(): array;

    /**
     * @return list<string>
     */
    public function tokens(): array;

    public function hasOption(string $name): bool;

    public function option(string $name, mixed $default = null): mixed;
}
