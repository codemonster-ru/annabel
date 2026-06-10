<?php

namespace Codemonster\HttpClient\Transports;

use Codemonster\HttpClient\Contracts\TransportInterface;
use Codemonster\HttpClient\HttpClientException;
use Codemonster\HttpClient\HttpResponse;
use Codemonster\HttpClient\RequestData;

class StreamTransport implements TransportInterface
{
    public function send(RequestData $request): HttpResponse
    {
        $headers = [];
        foreach ($request->headers() as $name => $value) {
            $headers[] = $name . ': ' . str_replace(["\r", "\n"], '', $value);
        }

        $context = stream_context_create([
            'http' => [
                'method' => $request->method(),
                'header' => implode("\r\n", $headers),
                'content' => $request->body() ?? '',
                'ignore_errors' => true,
                'timeout' => $request->timeout(),
            ],
        ]);

        $body = @file_get_contents($request->url(), false, $context);

        if ($body === false) {
            throw new HttpClientException("HTTP request to [{$request->url()}] failed.");
        }

        /** @var list<string> $responseHeaders */
        $responseHeaders = $http_response_header;

        return new HttpResponse(
            $this->status($responseHeaders),
            $body,
            $this->headers($responseHeaders),
        );
    }

    /**
     * @param list<string> $headers
     */
    protected function status(array $headers): int
    {
        $line = $headers[0] ?? '';

        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * @param list<string> $headers
     * @return array<string, list<string>>
     */
    protected function headers(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $normalized[strtolower(trim($name))][] = trim($value);
        }

        return $normalized;
    }
}
