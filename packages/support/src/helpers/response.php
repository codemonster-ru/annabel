<?php

use Codemonster\Http\Response;

if (!function_exists('response')) {
    /** @param array<string, string|string[]> $headers */
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        $response = app('response');
        if (!$response instanceof Response) {
            throw new RuntimeException('Response service is not available.');
        }

        if ($content !== '') {
            $response->setContent($content);
        }

        $response->setStatusCode($status)
            ->setHeaders($headers);

        return $response;
    }
}

if (!function_exists('json')) {
    /** @param array<string, string|string[]> $headers */
    function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($content === false) {
            $content = '{"error":"Failed to encode JSON"}';
            $status = 500;
        }

        $headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);

        return response($content, $status, $headers);
    }
}

if (!function_exists('abort')) {
    function abort(int $status, string $message = ''): never
    {
        throw new class($message ?: "HTTP {$status}", $status)
        extends \RuntimeException
        implements \Codemonster\Support\Contracts\HttpStatusExceptionInterface
        {
            protected int $statusCode;

            public function __construct(string $message, int $status)
            {
                $this->statusCode = $status;
                parent::__construct($message, $status);
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }
        };
    }
}
