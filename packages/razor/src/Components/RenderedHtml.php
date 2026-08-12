<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components;

use Stringable;

/**
 * HTML already rendered by the Razor compiler or a trusted component.
 *
 * This value does not sanitize input. Calling fromTrustedString() is an explicit
 * assertion that the supplied markup is safe to compose without further escaping.
 */
final readonly class RenderedHtml implements Stringable
{
    private function __construct(private string $value)
    {
    }

    public static function fromTrustedString(string $value): self
    {
        return new self($value);
    }

    public static function empty(): self
    {
        return new self('');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
