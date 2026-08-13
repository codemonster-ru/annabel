<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\ComponentRenderContext;
use Codemonster\Razor\Components\Contracts\ComponentInterface;
use Codemonster\Razor\Components\RenderedHtml;
use Codemonster\View\View;
use PHPUnit\Framework\TestCase;

class ViewServiceProviderTest extends TestCase
{
    public function test_view_service_is_registered(): void
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../../');

        $this->assertTrue($app->getContainer()->has(View::class));
        $this->assertInstanceOf(View::class, $app->getView());
    }

    public function test_application_components_are_available_to_the_razor_engine(): void
    {
        Application::resetInstance();

        $basePath = sys_get_temp_dir() . '/annabel-view-provider-' . bin2hex(random_bytes(8));
        mkdir($basePath . '/resources/views', 0777, true);
        file_put_contents($basePath . '/resources/views/component.razor.php', '<test-message />');

        try {
            $app = new Application($basePath);
            $components = $app->make(ComponentRegistry::class);
            $components->registerPrefix('test', [
                'message' => new class () implements ComponentInterface {
                    public function render(ComponentRenderContext $context): RenderedHtml
                    {
                        return RenderedHtml::fromTrustedString('<p>Registered component</p>');
                    }
                },
            ]);

            $this->assertSame('<p>Registered component</p>', $app->getView()->render('component'));
        } finally {
            $this->removeDirectory($basePath);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
            $path = $directory . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
