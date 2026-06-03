<?php

use Codemonster\Http\Request;
use Codemonster\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testDefaultResponse(): void
    {
        $response = new Response('Hello');

        $this->assertSame('Hello', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
    }

    public function testJsonResponse(): void
    {
        $response = Response::json(['ok' => true]);

        $this->assertTrue($response->isJson());
        $this->assertStringContainsString('"ok": true', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testJsonResponseHonorsOptions(): void
    {
        $response = Response::json(['url' => 'https://example.com'], 200, [], JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('https://example.com', $response->getContent());
        $this->assertStringNotContainsString('https:\\/\\/example.com', $response->getContent());
    }

    public function testRedirectResponse(): void
    {
        $response = Response::redirect('/login');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertSame('/login', $response->getHeaders()['Location']);
    }

    public function testSendForHeadSkipsOutput(): void
    {
        $request = new Request('HEAD', '/');
        $response = new Response('Hello');

        ob_start();
        $response->sendFor($request);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testSendHeadSkipsOutput(): void
    {
        $response = new Response('Hello');

        ob_start();
        $response->sendHead();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testSendThrowsWhenHeadersAlreadySent(): void
    {
        $response = new class('Hello') extends Response {
            protected function isCli(): bool
            {
                return false;
            }

            protected function headersSent(?string &$file = null, ?int &$line = null): bool
            {
                $file = 'test.php';
                $line = 10;

                return true;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test.php:10');
        $response->send();
    }

    public function testNoBodyStatusSkipsOutput(): void
    {
        $response = new Response('Hello', 204, ['Content-Type' => 'text/plain']);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testNotModifiedSkipsOutput(): void
    {
        $response = new Response('Hello', 304, ['Content-Type' => 'text/plain']);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testHeaderManipulation(): void
    {
        $response = new Response('Hello', 200);
        $response->header('X-Test', '1');

        $this->assertTrue($response->hasHeader('x-test'));
        $this->assertSame('1', $response->getHeaders()['X-Test']);
    }

    public function testWithStatusDoesNotMutateOriginal(): void
    {
        $response = new Response('Hello', 200);
        $next = $response->withStatus(201);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(201, $next->getStatusCode());
    }

    public function testWithoutHeadersDoesNotMutateOriginal(): void
    {
        $response = (new Response('Hello', 200))->header('X-Test', '1');
        $next = $response->withoutHeaders();

        $this->assertTrue($response->hasHeader('X-Test'));
        $this->assertSame([], $next->getHeaders());
    }

    public function testWithTypeDoesNotMutateOriginal(): void
    {
        $response = new Response('Hello', 200);
        $next = $response->withType('text/plain');

        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertSame('text/plain', $next->getHeaders()['Content-Type']);
    }

    public function testWithHeaderDoesNotMutateOriginal(): void
    {
        $response = new Response('Hello');
        $next = $response->withHeader('X-Test', '1');

        $this->assertFalse($response->hasHeader('X-Test'));
        $this->assertTrue($next->hasHeader('X-Test'));
    }

    public function testWithHeadersDoesNotMutateOriginal(): void
    {
        $response = new Response('Hello');
        $next = $response->withHeaders(['X-Test' => '1']);

        $this->assertFalse($response->hasHeader('X-Test'));
        $this->assertTrue($next->hasHeader('X-Test'));
    }

    public function testWithoutHeaderDoesNotMutateOriginal(): void
    {
        $response = (new Response('Hello'))->header('X-Test', '1');
        $next = $response->withoutHeader('x-test');

        $this->assertTrue($response->hasHeader('X-Test'));
        $this->assertFalse($next->hasHeader('X-Test'));
    }

    public function testWithCookieDoesNotMutateOriginal(): void
    {
        $response = new Response('Hello');
        $next = $response->withCookie('session', 'abc', ['path' => '/', 'httponly' => true]);

        $this->assertSame([], $this->getCookies($response));
        $this->assertArrayHasKey('session', $this->getCookies($next));
    }

    public function testCookieMutatesOriginal(): void
    {
        $response = new Response('Hello');
        $response->cookie('session', 'abc');

        $this->assertArrayHasKey('session', $this->getCookies($response));
    }

    public function testSameSiteNoneForcesSecure(): void
    {
        $response = (new Response('Hello'))->withCookie('session', 'abc', ['samesite' => 'none']);
        $cookies = $this->getCookies($response);

        $this->assertStringContainsString('SameSite=None', $cookies['session']);
        $this->assertStringContainsString('Secure', $cookies['session']);
    }

    public function testSetCookieHeaderArrayDoesNotReplace(): void
    {
        $response = new class('Hello') extends Response {
            public array $sent = [];

            protected function isCli(): bool
            {
                return false;
            }

            protected function headersSent(?string &$file = null, ?int &$line = null): bool
            {
                return false;
            }

            protected function sendHeader(string $name, string $value, bool $replace): void
            {
                $this->sent[] = [$name, $value, $replace];
            }
        };

        $response->header('Set-Cookie', ['a=1', 'b=2']);
        $response->cookie('c', '3');
        ob_start();
        $response->send();
        ob_end_clean();

        $this->assertSame(
            [
                ['Set-Cookie', 'a=1', false],
                ['Set-Cookie', 'b=2', false],
                ['Set-Cookie', 'c=3', false],
            ],
            array_values(array_filter($response->sent, static function (array $item): bool {
                return $item[0] === 'Set-Cookie';
            }))
        );
    }

    public function testWithoutCookieDoesNotMutateOriginal(): void
    {
        $response = (new Response('Hello'))->withCookie('session', 'abc');
        $next = $response->withoutCookie('session');

        $this->assertArrayHasKey('session', $this->getCookies($response));
        $this->assertSame([], $this->getCookies($next));
    }

    public function testHeaderOverwriteIsCaseInsensitive(): void
    {
        $response = new Response();
        $response->header('Content-Type', 'text/plain');
        $response->header('content-type', 'application/json');

        $this->assertTrue($response->hasHeader('CONTENT-TYPE'));
        $this->assertSame(1, count($response->getHeaders()));
        $this->assertSame('application/json', $response->getHeaders()['content-type']);
    }

    public function testSetContentThrowsOnJsonEncodingError(): void
    {
        $this->expectException(\RuntimeException::class);
        $response = new Response();
        $handle = fopen('php://memory', 'r');

        try {
            $response->setContent(['handle' => $handle]);
        } finally {
            fclose($handle);
        }
    }

    public function testToStringCastsContent(): void
    {
        $response = new Response('OK');

        $this->assertSame('OK', (string)$response);
    }

    private function getCookies(Response $response): array
    {
        $reflection = new ReflectionProperty($response, 'cookies');
        $reflection->setAccessible(true);

        return $reflection->getValue($response);
    }
}
