<?php

namespace Codemonster\Razor\Tests;

use Codemonster\Razor\RazorEngine;
use Codemonster\View\Locator\DefaultLocator;
use PHPUnit\Framework\TestCase;

class RazorEngineTest extends TestCase
{
    protected string $views;
    protected string $cache;

    protected function setUp(): void
    {
        $this->views = __DIR__ . '/views';
        $this->cache = __DIR__ . '/cache';

        if (!is_dir($this->views)) {
            mkdir($this->views, 0777, true);
        }

        if (!is_dir($this->cache)) {
            mkdir($this->cache, 0777, true);
        }

        file_put_contents($this->views . '/welcome.razor.php', '<h1>Hello, {{ $user }}</h1>');
    }

    public function testRendersTemplate(): void
    {
        $locator = new DefaultLocator([$this->views]);
        $engine = new RazorEngine($locator, 'razor.php', $this->cache);

        $html = $engine->render('welcome', ['user' => 'Vasya']);

        $this->assertStringContainsString('Hello, Vasya', $html);
    }
}
