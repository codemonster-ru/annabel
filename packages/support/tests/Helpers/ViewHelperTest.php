<?php

namespace Codemonster\Support\Tests\Helpers;

use Codemonster\Http\Response;
use Codemonster\View\Engines\PhpEngine;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\View;
use PHPUnit\Framework\TestCase;

class ViewHelperTest extends TestCase
{
    protected string $tempViewPath;

    protected function setUp(): void
    {
        $this->tempViewPath = sys_get_temp_dir() . '/views_' . uniqid();

        mkdir($this->tempViewPath);

        file_put_contents($this->tempViewPath . '/home.php', '<h1>Hello <?= $user ?></h1>');

        $locator = new DefaultLocator([$this->tempViewPath]);
        $engine = new PhpEngine($locator);
        $view = new View(['php' => $engine], 'php');

        app()->singleton('view', fn () => $view);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempViewPath . '/*.php'));

        rmdir($this->tempViewPath);
    }

    public function testViewReturnsInstance()
    {
        $this->assertInstanceOf(View::class, view());
    }

    public function testViewRendersResponse()
    {
        $response = view('home', ['user' => 'Vasya']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('Vasya', $response->getContent());
    }

    public function testRenderReturnsHtmlString()
    {
        $html = render('home', ['user' => 'Vasya']);

        $this->assertIsString($html);
        $this->assertStringContainsString('<h1>', $html);
    }
}
