<?php

namespace Codemonster\HttpClient;

use Codemonster\HttpClient\Contracts\TransportInterface;
use Codemonster\HttpClient\Transports\StreamTransport;

class HttpClient
{
    protected string $baseUrl = '';
    /** @var array<string, string> */
    protected array $headers = [];
    protected float $timeout = 30.0;

    public function __construct(protected ?TransportInterface $transport = null)
    {
        $this->transport ??= new StreamTransport();
    }

    public function baseUrl(string $baseUrl): self
    {
        $clone = clone $this;
        $clone->baseUrl = rtrim($baseUrl, '/');

        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;

        foreach ($headers as $name => $value) {
            $clone->headers[$name] = $value;
        }

        return $clone;
    }

    public function timeout(float $seconds): self
    {
        if ($seconds <= 0) {
            throw new HttpClientException('HTTP client timeout must be greater than zero.');
        }

        $clone = clone $this;
        $clone->timeout = $seconds;

        return $clone;
    }

    public function acceptJson(): self
    {
        return $this->withHeader('Accept', 'application/json');
    }

    /** @param array<string, mixed> $query */
    public function get(string $url, array $query = []): HttpResponse
    {
        return $this->send('GET', $this->withQuery($url, $query));
    }

    /** @param array<string, mixed> $query */
    public function delete(string $url, array $query = []): HttpResponse
    {
        return $this->send('DELETE', $this->withQuery($url, $query));
    }

    public function post(string $url, mixed $data = null): HttpResponse
    {
        return $this->sendWithBody('POST', $url, $data);
    }

    public function put(string $url, mixed $data = null): HttpResponse
    {
        return $this->sendWithBody('PUT', $url, $data);
    }

    public function patch(string $url, mixed $data = null): HttpResponse
    {
        return $this->sendWithBody('PATCH', $url, $data);
    }

    public function send(string $method, string $url, ?string $body = null): HttpResponse
    {
        return $this->transport()->send(new RequestData(
            strtoupper($method),
            $this->url($url),
            $this->headers,
            $body,
            $this->timeout,
        ));
    }

    protected function sendWithBody(string $method, string $url, mixed $data): HttpResponse
    {
        $headers = $this->headers;
        $body = null;

        if ($data !== null) {
            $body = is_string($data) ? $data : $this->encodeJson($data);
            $headers['Content-Type'] ??= 'application/json';
        }

        return $this->withHeaders($headers)->send($method, $url, $body);
    }

    protected function url(string $url): string
    {
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        if ($this->baseUrl === '') {
            return $url;
        }

        return $this->baseUrl . '/' . ltrim($url, '/');
    }

    /** @param array<string, mixed> $query */
    protected function withQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($query);
    }

    protected function encodeJson(mixed $data): string
    {
        $json = json_encode($data);

        if ($json === false) {
            throw new HttpClientException('Failed to encode JSON request: ' . json_last_error_msg());
        }

        return $json;
    }

    protected function transport(): TransportInterface
    {
        if (!$this->transport) {
            $this->transport = new StreamTransport();
        }

        return $this->transport;
    }
}
