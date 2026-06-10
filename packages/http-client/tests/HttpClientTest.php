<?php

namespace Codemonster\HttpClient\Tests;

use Codemonster\HttpClient\Contracts\TransportInterface;
use Codemonster\HttpClient\HttpClient;
use Codemonster\HttpClient\HttpClientException;
use Codemonster\HttpClient\HttpResponse;
use Codemonster\HttpClient\RequestData;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    public function test_get_builds_request_with_base_url_headers_and_query(): void
    {
        $transport = new FakeTransport(new HttpResponse(200, '{"ok":true}', [
            'content-type' => ['application/json'],
        ]));

        $response = (new HttpClient($transport))
            ->baseUrl('https://api.example.com')
            ->acceptJson()
            ->get('/users', ['page' => 2]);

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('https://api.example.com/users?page=2', $request->url());
        self::assertSame('GET', $request->method());
        self::assertSame('application/json', $request->headers()['Accept'] ?? null);
        self::assertTrue($response->successful());
        self::assertSame(['ok' => true], $response->json());
    }

    public function test_post_encodes_json_body(): void
    {
        $transport = new FakeTransport(new HttpResponse(201));

        (new HttpClient($transport))->post('https://api.example.com/users', ['name' => 'Annabel']);

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->method());
        self::assertSame('{"name":"Annabel"}', $request->body());
        self::assertSame('application/json', $request->headers()['Content-Type'] ?? null);
    }

    public function test_failed_response_can_throw(): void
    {
        $this->expectException(HttpClientException::class);

        (new HttpResponse(500, 'Broken'))->throw();
    }

    public function test_timeout_must_be_positive(): void
    {
        $this->expectException(HttpClientException::class);

        (new HttpClient())->timeout(0);
    }
}

class FakeTransport implements TransportInterface
{
    public ?RequestData $lastRequest = null;

    public function __construct(private HttpResponse $response)
    {
    }

    public function send(RequestData $request): HttpResponse
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}
