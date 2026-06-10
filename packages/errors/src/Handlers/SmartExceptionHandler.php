<?php

namespace Codemonster\Errors\Handlers;

use Closure;
use Codemonster\Errors\Contracts\ExceptionHandlerInterface;
use Codemonster\Http\Response;
use Throwable;

class SmartExceptionHandler implements ExceptionHandlerInterface
{
    protected ?Closure $viewRenderer;
    protected bool $debug;
    protected string $templatePath;

    /**
     * Order: errors.debug (debug=true), errors.{status}, errors.generic, fallback plain-text.
     */
    public function __construct(?callable $viewRenderer = null, bool $debug = false, ?string $templatePath = null)
    {
        $this->viewRenderer = $viewRenderer ? Closure::fromCallable($viewRenderer) : null;
        $this->debug = $debug;
        $this->templatePath = $templatePath ?? (dirname(__DIR__, 2) . '/resources/views/errors');
    }

    public function handle(Throwable $e): Response
    {
        $status = 500;

        if (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();

            if (is_int($statusCode)) {
                $status = $statusCode;
            }
        }

        if ($status < 100 || $status > 599) {
            $status = 500;
        }

        if ($this->debug) {
            return $this->renderTemplate('errors.debug', ['exception' => $e], $status) ?? $this->fallbackDebug($e, $status);
        }

        return $this->renderTemplate(
            "errors.{$status}",
            [
                'status' => $status,
                'message' => $e->getMessage(),
                'exception' => $e,
            ],
            $status,
        )
            ?? $this->renderTemplate(
                'errors.generic',
                [
                    'status' => $status,
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ],
                $status,
            )
            ?? $this->fallbackPlain($e, $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function renderTemplate(string $template, array $data, int $status): ?Response
    {
        if ($this->viewRenderer) {
            try {
                $html = ($this->viewRenderer)($template, $data);

                if (is_string($html) && $html !== '') {
                    return new Response($html, $status, ['Content-Type' => 'text/html']);
                }
            } catch (Throwable $renderError) {
                if ($this->debug) {
                    throw $renderError;
                }
            }
        }

        $basePath = $this->templatePath;
        $fileMap = [
            'errors.generic' => "$basePath/generic.php",
            'errors.debug' => "$basePath/debug.php",
        ];

        if (!isset($fileMap[$template]) && preg_match('/^errors\.(\d{3})$/', $template, $matches)) {
            $fileMap[$template] = sprintf('%s/%s.php', $basePath, $matches[1]);
        }

        if (isset($fileMap[$template]) && is_file($fileMap[$template])) {
            ob_start();
            extract($data, EXTR_SKIP);

            include $fileMap[$template];

            $html = ob_get_clean();

            if ($html === false) {
                throw new \RuntimeException('Unable to read rendered error template.');
            }

            return new Response($html, $status, ['Content-Type' => 'text/html']);
        }

        return null;
    }

    protected function fallbackPlain(Throwable $e, int $status): Response
    {
        $content = sprintf(
            "HTTP %d\nAn unexpected error occurred.",
            $status,
        );

        return new Response($content, $status, ['Content-Type' => 'text/plain']);
    }

    protected function fallbackDebug(Throwable $e, int $status): Response
    {
        $content = sprintf(
            "[%s] %s\nin %s:%d\n\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        );

        return new Response($content, $status, ['Content-Type' => 'text/plain']);
    }
}
