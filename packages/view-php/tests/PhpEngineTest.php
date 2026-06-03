<?php

declare(strict_types=1);

use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Engines\PhpEngine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PhpEngineTest extends TestCase
{
    private string $fixtures;
    private string $global;
    private string $blog;

    protected function setUp(): void
    {
        $this->fixtures = __DIR__ . '/fixtures';
        $this->global   = $this->fixtures . '/global';
        $this->blog     = $this->fixtures . '/blog';
    }

    public function testRendersSimpleTemplate(): void
    {
        $locator = new DefaultLocator([$this->global]);
        $engine  = new PhpEngine($locator);
        $html = $engine->render('home', ['name' => 'Annabel']);

        $this->assertStringContainsString('<h1>Hello, Annabel!</h1>', $html);
    }

    public function testDotNotation(): void
    {
        $locator = new DefaultLocator([$this->global]);
        $engine  = new PhpEngine($locator);
        $html = $engine->render('emails.welcome', ['user' => 'Kirill']);

        $this->assertStringContainsString('Welcome, Kirill', $html);
    }

    public function testNamespaces(): void
    {
        $locator = new DefaultLocator([$this->global]);
        $locator->addPath($this->blog, 'blog');
        $engine  = new PhpEngine($locator);
        $html = $engine->render('blog::post.show', []);

        $this->assertStringContainsString('blog post show', $html);
    }

    public function testMultipleExtensionsOrder(): void
    {
        $locator = new DefaultLocator([$this->global]);
        $engine  = new PhpEngine($locator, ['phtml', 'php']);
        $html = $engine->render('custom', ['x' => 1]);

        $this->assertStringContainsString('custom.phtml', $html);

        $engine  = new PhpEngine($locator, ['php', 'phtml']);
        $html = $engine->render('custom', ['x' => 2]);

        $this->assertStringContainsString('custom.php', $html);
    }

    public function testMissingTemplateThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $locator = new DefaultLocator([$this->global]);
        $engine  = new PhpEngine($locator);
        $engine->render('missing', []);
    }

    public function testTraversalBlockedByLocator(): void
    {
        $this->expectException(RuntimeException::class);

        $locator = new DefaultLocator([$this->global]);
        $engine  = new PhpEngine($locator);
        $engine->render('../secret', []);
    }
}
