<?php

namespace Codemonster\Razor;

use Codemonster\View\Contracts\SupportsInspectionInterface;
use Codemonster\View\EngineInterface;
use Codemonster\View\Locator\LocatorInterface;

class RazorEngine implements EngineInterface, SupportsInspectionInterface
{
    protected LocatorInterface $locator;
    protected Compiler $compiler;
    /** @var list<string> */
    protected array $extensions;

    /**
     * @param string|list<string> $extensions
     */
    public function __construct(LocatorInterface $locator, array|string $extensions = 'razor.php', ?string $cachePath = null)
    {
        $this->locator = $locator;
        $this->extensions = (array) $extensions;
        $this->compiler = new Compiler($cachePath ?? sys_get_temp_dir() . '/razor_cache');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $path = $this->locator->resolve($view, $this->extensions);
        $compiled = $this->compiler->compile($path);

        extract($data, EXTR_SKIP);

        ob_start();

        include $compiled;

        $content = ob_get_clean();

        if ($content === false) {
            throw new \RuntimeException('Unable to read rendered Razor output.');
        }

        return $content;
    }

    public function getLocator(): LocatorInterface
    {
        return $this->locator;
    }

    /**
     * @return list<string>
     */
    public function getExtensions(): array
    {
        return (array) $this->extensions;
    }
}
