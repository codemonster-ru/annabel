<?php

namespace Annabel\View;

use Annabel\View\Engines\PhpEngine;
use Annabel\View\Engines\SsrEngine;

class View
{
    protected PhpEngine $phpEngine;
    protected ?SsrEngine $ssrEngine = null;

    public function __construct(PhpEngine $phpEngine, ?SsrEngine $ssrEngine = null)
    {
        $this->phpEngine = $phpEngine;
        $this->ssrEngine = $ssrEngine;
    }

    public function render(string $view, array $data = [], bool $useSsr = false): string
    {
        if ($useSsr && $this->ssrEngine) {
            return $this->ssrEngine->render($view, $data);
        }

        return $this->phpEngine->render($view, $data);
    }
}
