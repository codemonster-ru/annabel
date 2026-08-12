<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components;

use Closure;

final class ComponentRenderContext
{
    /** @var array<string, RenderedHtml> */
    private array $renderedSlots = [];

    /**
     * @param array<string, mixed> $props
     * @param array<string, Closure(): RenderedHtml> $slots
     */
    public function __construct(
        private readonly array $props,
        private readonly array $slots,
    ) {
    }

    /** @return array<string, mixed> */
    public function props(): array
    {
        return $this->props;
    }

    public function hasProp(string $name): bool
    {
        return array_key_exists($name, $this->props);
    }

    public function prop(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->props) ? $this->props[$name] : $default;
    }

    public function hasSlot(string $name): bool
    {
        return isset($this->slots[$name]);
    }

    public function slot(string $name): RenderedHtml
    {
        if (!isset($this->slots[$name])) {
            return RenderedHtml::empty();
        }

        return $this->renderedSlots[$name] ??= ($this->slots[$name])();
    }
}
