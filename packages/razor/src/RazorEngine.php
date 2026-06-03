<?php

namespace Codemonster\Razor;

use Codemonster\View\EngineInterface;
use Codemonster\View\Locator\LocatorInterface;
use Codemonster\View\Contracts\SupportsInspectionInterface;

class RazorEngine implements EngineInterface, SupportsInspectionInterface
{
    protected LocatorInterface $locator;
    protected Compiler $compiler;
    protected array $extensions;

    public function __construct(LocatorInterface $locator, array|string $extensions = 'razor.php', ?string $cachePath = null)
    {
        $this->locator = $locator;
        $this->extensions = (array) $extensions;
        $this->compiler = new Compiler($cachePath ?? sys_get_temp_dir() . '/razor_cache');
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->locator->resolve($view, $this->extensions);
        $compiled = $this->compiler->compile($path);

        extract($data, EXTR_SKIP);

        ob_start();

        include $compiled;

        return ob_get_clean();
    }

    public function getLocator(): LocatorInterface
    {
        return $this->locator;
    }

    public function getExtensions(): array
    {
        return (array) $this->extensions;
    }
}
