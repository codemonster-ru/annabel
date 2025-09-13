<?php

namespace Annabel\View\Engines;

use Annabel\SSR\Bridge;

class SsrEngine
{
    protected Bridge $bridge;

    public function __construct(Bridge $bridge)
    {
        $this->bridge = $bridge;
    }

    public function render(string $view, array $data = []): string
    {
        return $this->bridge->render($view, $data);
    }
}
