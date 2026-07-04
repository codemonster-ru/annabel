<?php

namespace Codemonster\Cms\Tests\Unit\Pages;

use Codemonster\Cms\Modules\Pages\Models\Page;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    public function testItNormalizesSlugs(): void
    {
        self::assertSame('hello-world', Page::normalizeSlug(' Hello, World! '));
        self::assertSame('one-two-three', Page::normalizeSlug('one/two three'));
    }

    public function testItValidatesPublicSlugs(): void
    {
        self::assertTrue(Page::validSlug('home'));
        self::assertTrue(Page::validSlug('a'));
        self::assertTrue(Page::validSlug('about-us'));

        self::assertFalse(Page::validSlug(''));
        self::assertFalse(Page::validSlug('-about'));
        self::assertFalse(Page::validSlug('about-'));
        self::assertFalse(Page::validSlug('about_us'));
    }
}
