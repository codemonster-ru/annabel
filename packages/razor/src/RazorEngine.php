<?php

declare(strict_types=1);

namespace Codemonster\Razor;

use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\Contracts\ComponentResolverInterface;
use Codemonster\Razor\Exceptions\RazorException;
use Codemonster\Razor\Internal\RenderContext;
use Codemonster\View\Contracts\SupportsInspectionInterface;
use Codemonster\View\EngineInterface;
use Codemonster\View\Locator\LocatorInterface;
use Throwable;

class RazorEngine implements EngineInterface, SupportsInspectionInterface
{
    protected LocatorInterface $locator;
    protected Compiler $compiler;
    protected ComponentResolverInterface $components;

    /** @var list<string> */
    protected array $extensions;

    /**
     * @param string|list<string> $extensions
     */
    public function __construct(
        LocatorInterface $locator,
        array|string $extensions = 'razor.php',
        ?string $cachePath = null,
        ?ComponentResolverInterface $components = null,
    ) {
        $this->locator = $locator;
        $this->extensions = array_values((array) $extensions);
        $this->components = $components ?? new ComponentRegistry();
        $this->compiler = new Compiler($cachePath ?? sys_get_temp_dir() . '/razor_cache', $this->components);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $context = new RenderContext(
            fn (string $name, array $scope, RenderContext $runtime): string => $this->evaluate($name, $scope, $runtime),
            $this->components,
        );

        return $context->render($view, $data);
    }

    public function getLocator(): LocatorInterface
    {
        return $this->locator;
    }

    /** @return list<string> */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /** @param array<string, mixed> $data */
    private function evaluate(string $view, array $data, RenderContext $__razor): string
    {
        try {
            $path = $this->locator->resolve($view, $this->extensions);
            $compiled = $this->compiler->compile($path);
        } catch (Throwable $exception) {
            if ($exception instanceof RazorException) {
                throw $exception;
            }

            throw new RazorException('Unable to resolve Razor view [' . $view . '].', 0, $exception);
        }

        $__razorData = $data;
        extract($data, EXTR_SKIP);
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            include $compiled;

            $content = ob_get_clean();

            if ($content === false) {
                throw new RazorException('Unable to read rendered Razor output for [' . $view . '].');
            }

            return $content;
        } catch (Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if ($exception instanceof RazorException) {
                throw $exception;
            }

            throw new RazorException('Unable to render Razor view [' . $view . '].', 0, $exception);
        }
    }
}
