<?php

declare(strict_types=1);

namespace Codemonster\View\Locator;

interface LocatorInterface
{
    public function resolve(string $name, string|array $extensions): string;
}
