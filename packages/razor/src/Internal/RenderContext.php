<?php

declare(strict_types=1);

namespace Codemonster\Razor\Internal;

use Closure;
use Codemonster\Razor\Components\ComponentRenderContext;
use Codemonster\Razor\Components\Contracts\ComponentResolverInterface;
use Codemonster\Razor\Components\RenderedHtml;
use Codemonster\Razor\Exceptions\RazorException;
use Stringable;

/** @internal Runtime used by compiled Razor templates. */
final class RenderContext
{
    /** @var array<string, string> */
    private array $sections = [];

    /** @var array<string, int> */
    private array $sectionLayers = [];

    /** @var list<string> */
    private array $sectionStack = [];

    private ?string $parent = null;

    private int $layer = 0;

    private int $includeDepth = 0;

    /** @param Closure(string, array<string, mixed>, self): string $renderer */
    public function __construct(
        private readonly Closure $renderer,
        private readonly ComponentResolverInterface $components,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function render(string $view, array $data): string
    {
        $lineage = [$view];
        $output = ($this->renderer)($view, $data, $this);

        while ($this->parent !== null) {
            if (!isset($this->sections['content']) && trim($output) !== '') {
                $this->sections['content'] = $output;
                $this->sectionLayers['content'] = $this->layer;
            }

            $parent = $this->parent;
            $this->parent = null;

            if (in_array($parent, $lineage, true)) {
                $lineage[] = $parent;

                throw new RazorException('Circular Razor layout inheritance: ' . implode(' -> ', $lineage));
            }

            $lineage[] = $parent;
            $this->layer++;
            $output = ($this->renderer)($parent, $data, $this);
        }

        if ($this->sectionStack !== []) {
            throw new RazorException('A Razor section was not closed.');
        }

        return $output;
    }

    /**
     * @param array<string, mixed> $scope
     * @param array<string, mixed> $data
     */
    public function includeView(string $view, array $scope, array $data = []): string
    {
        $this->includeDepth++;

        try {
            return ($this->renderer)($view, array_replace($scope, $data), $this);
        } finally {
            $this->includeDepth--;
        }
    }

    public function extend(string $view): void
    {
        if ($view === '') {
            throw new RazorException('A Razor layout name must not be empty.');
        }

        if ($this->includeDepth > 0) {
            throw new RazorException('An included Razor template cannot extend a layout.');
        }

        if ($this->parent !== null) {
            throw new RazorException('A Razor template may extend only one layout.');
        }

        $this->parent = $view;
    }

    public function startSection(string $name): void
    {
        if ($name === '') {
            throw new RazorException('A Razor section name must not be empty.');
        }

        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);

        if ($name === null) {
            throw new RazorException('Unexpected @endsection without a matching @section.');
        }

        $content = ob_get_clean();

        if ($content === false) {
            throw new RazorException("Unable to capture Razor section [{$name}].");
        }

        $existingLayer = $this->sectionLayers[$name] ?? null;

        if ($existingLayer === null || $existingLayer === $this->layer) {
            $this->sections[$name] = $content;
            $this->sectionLayers[$name] = $this->layer;
        }
    }

    public function yieldSection(string $name, mixed $default = ''): string
    {
        return $this->sections[$name] ?? $this->stringify($default);
    }

    public function escape(mixed $value): string
    {
        return htmlspecialchars(
            $this->stringify($value),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
    }

    public function raw(mixed $value): string
    {
        return $this->stringify($value);
    }

    /**
     * @param array<string, mixed> $scope
     * @param Closure(array<string, mixed>): void $renderer
     * @return Closure(): RenderedHtml
     */
    public function componentSlot(array $scope, Closure $renderer): Closure
    {
        return static function () use ($scope, $renderer): RenderedHtml {
            $bufferLevel = ob_get_level();
            ob_start();

            try {
                $renderer($scope);
                $content = ob_get_clean();

                if ($content === false) {
                    throw new RazorException('Unable to read rendered Razor component slot.');
                }

                return RenderedHtml::fromTrustedString($content);
            } catch (\Throwable $exception) {
                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }

                throw $exception;
            }
        };
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, Closure(): RenderedHtml> $slots
     */
    public function renderComponent(string $tag, array $props, array $slots): RenderedHtml
    {
        $component = $this->components->resolve($tag);

        if ($component === null) {
            throw new RazorException("Unknown Razor component [{$tag}].");
        }

        return $component->render(new ComponentRenderContext($props, $slots));
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        throw new RazorException(sprintf('Unable to render value of type [%s].', get_debug_type($value)));
    }
}
