<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components;

use Codemonster\Razor\Components\RenderedHtml;
use PHPUnit\Framework\TestCase;

final class RenderedHtmlTest extends TestCase
{
    public function testPreservesExplicitlyTrustedMarkup(): void
    {
        $html = RenderedHtml::fromTrustedString('<strong>Trusted & rendered</strong>');

        self::assertSame('<strong>Trusted & rendered</strong>', $html->value());
        self::assertSame('<strong>Trusted & rendered</strong>', (string) $html);
    }

    public function testCreatesAnEmptyTrustedValue(): void
    {
        self::assertSame('', RenderedHtml::empty()->value());
    }
}
