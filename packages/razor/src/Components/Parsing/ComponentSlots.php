<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Parsing;

final readonly class ComponentSlots
{
    /** @param array<string, string> $named */
    public function __construct(
        public string $default,
        public array $named,
    ) {
    }
}
