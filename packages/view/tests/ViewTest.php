<?php

namespace Codemonster\View\Tests;

use Codemonster\View\Contracts\SupportsInspectionInterface;
use Codemonster\View\EngineInterface;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Locator\LocatorInterface;
use Codemonster\View\View;
use PHPUnit\Framework\TestCase;

class DummyEngine implements EngineInterface
{
    public function render(string $view, array $data = []): string
    {
        return strtoupper($view) . ':' . json_encode($data);
    }
}

class InspectableEngine implements EngineInterface, SupportsInspectionInterface
{
    public function __construct(
        private LocatorInterface $locator,
        private array $extensions,
        private string $tag,
    ) {
    }

    public function render(string $view, array $data = []): string
    {
        return $this->tag . ':' . $view;
    }

    public function getLocator(): LocatorInterface
    {
        return $this->locator;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }
}

class LocatorRenderingEngine implements EngineInterface, SupportsInspectionInterface
{
    public function __construct(
        private LocatorInterface $locator,
        private array $extensions,
    ) {
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->locator->resolve($view, $this->extensions);

        return basename($path);
    }

    public function getLocator(): LocatorInterface
    {
        return $this->locator;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }
}

class ViewTest extends TestCase
{
    public function testRenderWithDummyEngine()
    {
        $view = new View(['dummy' => new DummyEngine()], 'dummy');

        $output = $view->render('hello', ['name' => 'World']);

        $this->assertEquals('HELLO:{"name":"World"}', $output);
    }

    public function testAutoDetectsEngineByExtension(): void
    {
        $fixtures = __DIR__ . '/fixtures';
        $global = $fixtures . '/global';
        $override = $fixtures . '/override';

        $phpLocator = new DefaultLocator([$override]);
        $phtmlLocator = new DefaultLocator([$global]);

        $phpEngine = new InspectableEngine($phpLocator, ['php'], 'php');
        $phtmlEngine = new InspectableEngine($phtmlLocator, ['phtml'], 'phtml');

        $view = new View(
            ['php' => $phpEngine, 'phtml' => $phtmlEngine],
            'php',
        );

        $output = $view->render('custom');

        $this->assertSame('phtml:custom', $output);
    }

    public function testFallsBackToDefaultEngineWhenNoMatch(): void
    {
        $fixtures = __DIR__ . '/fixtures';
        $override = $fixtures . '/override';

        $phpLocator = new DefaultLocator([$override]);
        $phpEngine = new InspectableEngine($phpLocator, ['php'], 'php');
        $dummyEngine = new DummyEngine();

        $view = new View(
            ['php' => $phpEngine, 'dummy' => $dummyEngine],
            'dummy',
        );

        $output = $view->render('missing');

        $this->assertSame('MISSING:[]', $output);
    }

    public function testAddNamespaceRegistersPathsOnDefaultLocator(): void
    {
        $fixtures = __DIR__ . '/fixtures';
        $global = $fixtures . '/global';
        $blog = $fixtures . '/blog';

        $locator = new DefaultLocator([$global]);
        $engine = new LocatorRenderingEngine($locator, ['php']);

        $view = new View(['php' => $engine], 'php');
        $view->addNamespace('blog', $blog);

        $output = $view->render('blog::post.show');

        $this->assertSame('show.php', $output);
    }

    public function testThrowsWhenEngineNotFound()
    {
        $this->expectException(\RuntimeException::class);

        $view = new View([], 'php');
        $view->render('home');
    }
}
