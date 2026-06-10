<?php

declare(strict_types=1);

namespace Codemonster\View\Engines;

use Codemonster\View\EngineInterface;

final class SsrEngine implements EngineInterface
{
    private \Closure $renderBridge;

    public function __construct(object $bridge)
    {
        $render = [$bridge, 'render'];

        if (!\is_callable($render)) {
            throw new \InvalidArgumentException(
                'SSR bridge must have a render(string $view, array $data = []): string method.',
            );
        }

        $this->renderBridge = \Closure::fromCallable($render);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $result = ($this->renderBridge)($view, $data);

        if (!\is_string($result)) {
            throw new \RuntimeException('SSR bridge returned non-string response.');
        }

        return $result;
    }
}
