<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Contracts;

interface ComponentResolverInterface
{
    /** Whether the resolver owns the tag's prefix, including unknown component names. */
    public function handles(string $tag): bool;

    public function resolve(string $tag): ?ComponentInterface;

    /** Stable signature of registrations that affect compiled component tags. */
    public function cacheSignature(): string;
}
