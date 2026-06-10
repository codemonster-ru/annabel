<?php

namespace Codemonster\Errors\Tests;

use Codemonster\Errors\Handlers\SmartExceptionHandler;
use Codemonster\Http\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SmartExceptionHandlerTest extends TestCase
{
    protected function makeRenderer(): callable
    {
        return function (string $template, array $data) {
            return "<html><body>Template: {$template}, Message: {$data['exception']->getMessage()}</body></html>";
        };
    }

    public function testRendersDebugTemplateWhenDebugEnabled(): void
    {
        $handler = new SmartExceptionHandler($this->makeRenderer(), true);
        $response = $handler->handle(new RuntimeException('Debug error'));

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('errors.debug', (string) $response);
        self::assertSame(500, $response->getStatusCode());
    }

    public function testRendersGenericTemplateWhenDebugDisabled(): void
    {
        $handler = new SmartExceptionHandler($this->makeRenderer(), false);
        $response = $handler->handle(new RuntimeException('Generic error'));

        self::assertStringContainsString('errors.500', (string) $response);
        self::assertSame(500, $response->getStatusCode());
    }

    public function testRenders404TemplateForNotFoundException(): void
    {
        $exception = new class ('Page missing', 404) extends RuntimeException {
            public function getStatusCode(): int
            {
                return 404;
            }
        };

        $handler = new SmartExceptionHandler($this->makeRenderer(), false);
        $response = $handler->handle($exception);

        self::assertStringContainsString('errors.404', (string) $response);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testRendersStatusTemplateFromFileWhenPresent(): void
    {
        $basePath = sys_get_temp_dir() . '/codemonster-errors-' . uniqid('', true);
        $templatePath = $basePath . '/404.php';

        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        try {
            file_put_contents(
                $templatePath,
                "<?php echo 'status-template-' . (int) (\$status ?? 0);",
            );

            $exception = new class ('Page missing', 404) extends RuntimeException {
                public function getStatusCode(): int
                {
                    return 404;
                }
            };

            $handler = new SmartExceptionHandler(null, false, $basePath);
            $response = $handler->handle($exception);

            self::assertSame(404, $response->getStatusCode());
            self::assertStringContainsString('status-template-404', (string) $response);
        } finally {
            if (is_file($templatePath)) {
                unlink($templatePath);
            }

            if (is_dir($basePath)) {
                rmdir($basePath);
            }
        }
    }

    public function testThrowsWhenRendererFailsInDebugMode(): void
    {
        $renderer = static function (): string {
            throw new RuntimeException('View failed');
        };

        $handler = new SmartExceptionHandler($renderer, true);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('View failed');
        $handler->handle(new RuntimeException('Crash'));
    }
}
