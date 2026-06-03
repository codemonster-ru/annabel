<?php

declare(strict_types=1);

namespace Codemonster\View\Contracts;

use Codemonster\View\Locator\LocatorInterface;

interface SupportsInspectionInterface
{
    public function getLocator(): LocatorInterface;
    public function getExtensions(): array;
}
