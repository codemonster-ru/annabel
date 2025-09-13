<?php

use PHPUnit\Framework\TestCase;
use Annabel\Http\Response;

class ResponseTest extends TestCase
{
    public function testGetContentReturnsProvidedContent(): void
    {
        $response = new Response('Hello World');
        $this->assertSame('Hello World', $response->getContent());
    }

    public function testGetStatusCodeReturnsProvidedStatus(): void
    {
        $response = new Response('Error', 404);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetHeadersReturnsProvidedHeaders(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $response = new Response('{"ok":true}', 200, $headers);

        $this->assertSame($headers, $response->getHeaders());
    }

    public function testDefaultValues(): void
    {
        $response = new Response();

        $this->assertSame('', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
    }
}
