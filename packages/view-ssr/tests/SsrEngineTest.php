<?php

declare(strict_types=1);

use Codemonster\View\Engines\SsrEngine;
use PHPUnit\Framework\TestCase;

final class SsrEngineTest extends TestCase
{
    public function testRendersViaBridge(): void
    {
        $bridge = new class {
            public function render(string $view, array $data = []): string
            {
                return '<div data-view="' . $view . '">' . json_encode($data) . '</div>';
            }
        };

        $engine = new SsrEngine($bridge);
        $html = $engine->render('Home', ['x' => 1]);

        $this->assertStringContainsString('data-view="Home"', $html);
        $this->assertStringContainsString('"x":1', $html);
    }

    public function testRejectsBridgeWithoutRender(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $engine = new SsrEngine(new class {});
    }

    public function testRejectsNonStringResponse(): void
    {
        $this->expectException(\RuntimeException::class);

        $bridge = new class {
            public function render(string $view, array $data = []): array
            {
                return [$view, $data];
            }
        };

        $engine = new SsrEngine($bridge);
        $engine->render('home', []);
    }
}
