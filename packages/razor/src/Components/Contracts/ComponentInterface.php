<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Contracts;

use Codemonster\Razor\Components\ComponentRenderContext;
use Codemonster\Razor\Components\RenderedHtml;

interface ComponentInterface
{
    public function render(ComponentRenderContext $context): RenderedHtml;
}
