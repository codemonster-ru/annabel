<?php
declare(strict_types=1);

namespace Codemonster\View\Engines;

use Codemonster\View\EngineInterface;

final class SsrEngine implements EngineInterface
{
    public function __construct(private object $bridge)
    {
        if (!\method_exists($bridge, 'render')) {
            throw new \InvalidArgumentException(
                'SSR bridge must have a render(string $view, array $data = []): string method.'
            );
        }
    }

    public function render(string $view, array $data = []): string
    {
        $result = $this->bridge->render($view, $data);

        if (!\is_string($result)) {
            throw new \RuntimeException('SSR bridge returned non-string response.');
        }

        return $result;
    }
}
