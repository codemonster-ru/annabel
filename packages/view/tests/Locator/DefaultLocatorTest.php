<?php

declare(strict_types=1);

namespace Codemonster\View\Tests\Locator;

use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Locator\LocatorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DefaultLocatorTest extends TestCase
{
    private string $fx;
    private string $global;
    private string $override;
    private string $blog;

    protected function setUp(): void
    {
        $this->fx = __DIR__ . '/../fixtures';
        $this->global = $this->fx . '/global';
        $this->override = $this->fx . '/override';
        $this->blog = $this->fx . '/blog';
    }

    public function testImplementsInterface(): void
    {
        $locator = new DefaultLocator([$this->global]);

        $this->assertInstanceOf(LocatorInterface::class, $locator);
    }

    public function testResolvesFromGlobalBaseWithDotNotation(): void
    {
        $locator = new DefaultLocator([$this->global]);
        $file = $locator->resolve('home', 'php');

        $this->assertFileExists($file);
        $this->assertStringEndsWith('fixtures' . DIRECTORY_SEPARATOR . 'global' . DIRECTORY_SEPARATOR . 'home.php', $file);

        $file = $locator->resolve('emails.welcome', 'php');

        $this->assertFileExists($file);
        $this->assertStringEndsWith('global' . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR . 'welcome.php', $file);
    }

    public function testResolvesWithNamespace(): void
    {
        $locator = new DefaultLocator([$this->global]);
        $locator->addPath($this->blog, 'blog');

        $file = $locator->resolve('blog::post.show', 'php');

        $this->assertFileExists($file);
        $this->assertStringEndsWith('blog' . DIRECTORY_SEPARATOR . 'post' . DIRECTORY_SEPARATOR . 'show.php', $file);
    }

    public function testNamespaceFallsBackToGlobalIfNotFoundInNamespace(): void
    {
        $locator = new DefaultLocator([$this->global]);
        $locator->addPath($this->blog, 'blog');

        $file = $locator->resolve('blog::emails.welcome', 'php');

        $this->assertFileExists($file);
        $this->assertStringEndsWith('global' . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR . 'welcome.php', $file);
    }

    public function testMultipleBasePathsPreserveOrder(): void
    {
        $locator = new DefaultLocator([$this->override, $this->global]);
        $file = $locator->resolve('home', 'php');

        $contents = file_get_contents($file) ?: '';

        $this->assertStringContainsString('override home', $contents, 'Should prefer first base path');
    }

    public function testSupportsMultipleExtensionsWithOrder(): void
    {
        $locator = new DefaultLocator([$this->global]);

        $file = $locator->resolve('custom', ['phtml', 'php']);

        $this->assertStringEndsWith('custom.phtml', $file);

        $file = $locator->resolve('custom', ['php', 'phtml']);

        $this->assertStringEndsWith('custom.php', $file);
    }

    public function testBlocksPathTraversal(): void
    {
        $this->expectException(\RuntimeException::class);

        $locator = new DefaultLocator([$this->global]);
        $locator->resolve('../secret', 'php');
    }

    public function testThrowsIfViewNotFound(): void
    {
        $this->expectException(\RuntimeException::class);

        $locator = new DefaultLocator([$this->global]);
        $locator->resolve('missing.view', 'php');
    }

    public function testSkipsNonExistentPathsButRequiresAtLeastOneValid(): void
    {
        $locator = new DefaultLocator([$this->fx . '/nope', $this->global]);
        $file = $locator->resolve('home', 'php');

        $this->assertFileExists($file);

        $this->expectException(\RuntimeException::class);

        new DefaultLocator([$this->fx . '/nope', $this->fx . '/also-nope']);
    }
}
