<?php

declare(strict_types=1);

namespace Codemonster\View;

interface EngineInterface
{
    public function render(string $view, array $data = []): string;
}
