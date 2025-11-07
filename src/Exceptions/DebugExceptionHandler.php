<?php

namespace Codemonster\Annabel\Exceptions;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ExceptionHandlerInterface;
use Codemonster\Http\Response;
use Throwable;

class DebugExceptionHandler implements ExceptionHandlerInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Throwable $e): Response
    {
        try {
            $view = $this->app->getView();

            $html = $view->render('errors.debug', ['exception' => $e]);

            return new Response($html, 500, ['Content-Type' => 'text/html']);
        } catch (Throwable $inner) {
            $content = sprintf(
                "Internal Server Error: %s\nin %s:%d\n\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );

            return new Response($content, 500, ['Content-Type' => 'text/plain']);
        }
    }
}
