<?php

declare(strict_types=1);

namespace Codemonster\View\Locator;

interface LocatorInterface
{
    /** @param string|list<string> $extensions */
    public function resolve(string $name, string|array $extensions): string;
}
