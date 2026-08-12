<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Contracts;

interface ComponentProviderInterface
{
    public function prefix(): string;

    /** @return array<string, ComponentInterface> Components keyed by unprefixed kebab-case name. */
    public function components(): array;
}
