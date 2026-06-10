<?php

declare(strict_types=1);

namespace Codemonster\View;

interface EngineInterface
{
    /** @param array<string, mixed> $data */
    public function render(string $view, array $data = []): string;
}
