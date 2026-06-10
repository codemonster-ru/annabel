<?php

use Codemonster\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RequestTest extends TestCase
{
    public function testManualInstantiation(): void
    {
        $request = new Request('POST', '/api/test', ['page' => '1'], ['name' => 'Vasya'], ['Accept' => 'application/json']);

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame('POST', $request->method());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/test', $request->uri());
        $this->assertSame('1', $request->query('page'));
        $this->assertSame('Vasya', $request->input('name'));
        $this->assertTrue($request->wantsJson());
    }

    public function testFilesAndAllDoNotPolluteInput(): void
    {
        $files = [
            'avatar' => [
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/a',
                'error' => 0,
                'size' => 123,
            ],
        ];
        $request = new Request('POST', '/upload', ['page' => 1], ['name' => 'Vasya'], [], '', [], $files);

        $this->assertSame('Vasya', $request->input('name'));
        $this->assertSame($files, $request->files());
        $this->assertSame(
            [
                'page' => 1,
                'name' => 'Vasya',
                'avatar' => $files['avatar'],
            ],
            $request->all(),
        );
    }

    public function testFilesAreNormalized(): void
    {
        $files = [
            'avatar' => [
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/a',
                'error' => 0,
                'size' => 123,
            ],
            'photos' => [
                'name' => ['a.jpg', 'b.jpg'],
                'type' => ['image/jpeg', 'image/jpeg'],
                'tmp_name' => ['/tmp/a', '/tmp/b'],
                'error' => [0, 0],
                'size' => [123, 456],
            ],
        ];
        $request = new Request('POST', '/upload', [], [], [], '', [], $files);

        $this->assertSame($files['avatar'], $request->files('avatar'));
        $this->assertSame(
            [
                0 => [
                    'name' => 'a.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => '/tmp/a',
                    'error' => 0,
                    'size' => 123,
                ],
                1 => [
                    'name' => 'b.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => '/tmp/b',
                    'error' => 0,
                    'size' => 456,
                ],
            ],
            $request->files('photos'),
        );
    }

    public function testNestedFilesAreNormalized(): void
    {
        $files = [
            'attachments' => [
                'name' => [
                    'docs' => [
                        'a' => 'a.txt',
                        'b' => 'b.txt',
                    ],
                ],
                'type' => [
                    'docs' => [
                        'a' => 'text/plain',
                        'b' => 'text/plain',
                    ],
                ],
                'tmp_name' => [
                    'docs' => [
                        'a' => '/tmp/a',
                        'b' => '/tmp/b',
                    ],
                ],
                'error' => [
                    'docs' => [
                        'a' => 0,
                        'b' => 0,
                    ],
                ],
                'size' => [
                    'docs' => [
                        'a' => 10,
                        'b' => 20,
                    ],
                ],
            ],
        ];
        $request = new Request('POST', '/upload', [], [], [], '', [], $files);

        $this->assertSame(
            [
                'docs' => [
                    'a' => [
                        'name' => 'a.txt',
                        'type' => 'text/plain',
                        'tmp_name' => '/tmp/a',
                        'error' => 0,
                        'size' => 10,
                    ],
                    'b' => [
                        'name' => 'b.txt',
                        'type' => 'text/plain',
                        'tmp_name' => '/tmp/b',
                        'error' => 0,
                        'size' => 20,
                    ],
                ],
            ],
            $request->files('attachments'),
        );
        $this->assertSame('a.txt', $request->files('attachments.docs.a.name'));
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $request = new Request('GET', '/', [], [], ['X-Test' => '1']);

        $this->assertSame('1', $request->header('x-test'));
        $this->assertSame('1', $request->header('X-TEST'));
        $this->assertSame('1', $request->header('X-Test'));
        $this->assertSame(['X-Test' => '1'], $request->header());
    }

    public function testHeadersAndServerAccessors(): void
    {
        $headers = ['X-Test' => '1'];
        $server = ['REMOTE_ADDR' => '127.0.0.1'];
        $request = new Request('GET', '/', [], [], $headers, '', $server);

        $this->assertSame($headers, $request->headers());
        $this->assertSame($server, $request->server());
    }

    public function testRequestImmutabilityHelpers(): void
    {
        $request = new Request('GET', '/', ['page' => 1], ['name' => 'Vasya'], ['X-Test' => '1']);

        $withHeader = $request->withHeader('X-Next', '2');
        $withoutHeader = $request->withoutHeader('X-Test');
        $withQuery = $request->withQuery(['page' => 2]);
        $withInput = $request->withInput(['name' => 'Petya']);

        $this->assertSame(['X-Test' => '1'], $request->headers());
        $this->assertSame('2', $withHeader->header('x-next'));
        $this->assertNull($withoutHeader->header('x-test'));
        $this->assertSame(1, $request->query('page'));
        $this->assertSame(2, $withQuery->query('page'));
        $this->assertSame('Vasya', $request->input('name'));
        $this->assertSame('Petya', $withInput->input('name'));
    }

    public function testPsrServerRequestMethods(): void
    {
        $request = new Request('POST', 'https://example.com/api?foo=bar', ['foo' => 'bar'], ['name' => 'Vasya'], ['Accept' => 'application/json'], 'raw');

        $withAttribute = $request->withAttribute('user_id', 10);
        $withHeader = $request->withAddedHeader('Accept', 'text/html');

        $this->assertSame('/api?foo=bar', $request->getRequestTarget());
        $this->assertSame('https', $request->getUri()->getScheme());
        $this->assertSame('example.com', $request->getUri()->getHost());
        $this->assertSame(['foo' => 'bar'], $request->getQueryParams());
        $this->assertSame(['name' => 'Vasya'], $request->getParsedBody());
        $this->assertSame('raw', (string) $request->getBody());
        $this->assertSame(10, $withAttribute->getAttribute('user_id'));
        $this->assertSame(['application/json', 'text/html'], $withHeader->getHeader('Accept'));
    }

    public function testOnlyAndExcept(): void
    {
        $files = [
            'avatar' => [
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/a',
                'error' => 0,
                'size' => 123,
            ],
        ];
        $request = new Request('GET', '/', ['page' => 1], ['name' => 'Vasya'], [], '', [], $files);

        $this->assertSame(['page' => 1, 'name' => 'Vasya'], $request->only(['page', 'name']));
        $this->assertSame(['avatar' => $files['avatar']], $request->except(['page', 'name']));
    }

    public function testOnlyAndExceptWithDotNotation(): void
    {
        $data = [
            'user' => [
                'name' => 'Vasya',
                'email' => 'test@example.com',
            ],
            'meta' => [
                'count' => 1,
            ],
        ];
        $request = new Request('GET', '/', [], $data);

        $this->assertSame(
            [
                'user' => [
                    'name' => 'Vasya',
                ],
            ],
            $request->only(['user.name']),
        );
        $this->assertSame(
            [
                'user' => [
                    'email' => 'test@example.com',
                ],
                'meta' => [
                    'count' => 1,
                ],
            ],
            $request->except(['user.name']),
        );
    }

    public function testInputAndQueryWithDotNotation(): void
    {
        $request = new Request('GET', '/', ['meta' => ['page' => 2]], ['user' => ['name' => 'Vasya']]);

        $this->assertSame(2, $request->query('meta.page'));
        $this->assertSame('Vasya', $request->input('user.name'));
        $this->assertNull($request->query('meta.missing'));
        $this->assertSame('default', $request->query('meta.missing', 'default'));
    }

    public function testCaptureFromGlobals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/hello';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_GET = ['id' => '42'];
        $_POST = [];

        $request = Request::capture();

        $this->assertSame('GET', $request->method());
        $this->assertSame('/hello', $request->uri());
        $this->assertSame('42', $request->query('id'));
        $this->assertFalse($request->wantsJson());
    }

    public function testJsonBodyParsing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $json = json_encode(['foo' => 'bar']);

        file_put_contents('php://memory', $json);

        $request = new Request('POST', '/api', [], ['foo' => 'bar'], ['Content-Type' => 'application/json'], $json);

        $this->assertSame('bar', $request->input('foo'));
        $this->assertSame('/api', $request->uri());
    }

    public function testUrlencodedBodyParsingWhenPostIsEmpty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/api';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_POST = [];
        $rawBody = 'foo=bar&baz=1';

        $request = new Request('PUT', '/api', [], [], ['Content-Type' => 'application/x-www-form-urlencoded'], $rawBody);

        $this->assertSame('bar', $request->input('foo'));
        $this->assertSame('1', $request->input('baz'));
    }

    public function testFullUrlComposition(): void
    {
        $request = new Request('GET', '/user', [], [], [], '', ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on']);

        $this->assertSame('https://example.com/user', $request->fullUrl());
    }

    public function testUserAgentAndIpAndSecureDetection(): void
    {
        Request::setTrustedProxies([]);
        $server = [
            'HTTP_USER_AGENT' => 'TestAgent/1.0',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 10.0.0.2',
            'REMOTE_ADDR' => '192.168.1.5',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];
        $request = new Request('GET', '/', [], [], [], '', $server);

        $this->assertSame('TestAgent/1.0', $request->userAgent());
        $this->assertSame('192.168.1.5', $request->ip());
        $this->assertFalse($request->isSecure());
    }

    public function testFullUrlWithQuery(): void
    {
        $request = new Request('GET', '/user', ['page' => 1, 'sort' => 'name'], [], [], '', ['HTTP_HOST' => 'example.com']);

        $this->assertSame('http://example.com/user?page=1&sort=name', $request->fullUrlWithQuery());
    }

    public function testCaptureIncludesContentTypeAndAuthorization(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/secure',
            'CONTENT_TYPE' => 'application/json',
            'AUTHORIZATION' => 'Bearer 123',
        ];
        $_GET = [];
        $_POST = [];

        $request = Request::capture();

        $this->assertSame('application/json', $request->header('Content-Type'));
        $this->assertSame('Bearer 123', $request->header('Authorization'));
    }

    public function testMethodOverrideFromHeader(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/resource',
            'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PATCH',
        ];
        $_GET = [];
        $_POST = [];

        $request = Request::capture();

        $this->assertSame('PATCH', $request->method());
    }

    public function testMethodOverrideFromBody(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/resource',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
        $_GET = [];
        $_POST = ['_method' => 'PUT'];

        $request = Request::capture();

        $this->assertSame('PUT', $request->method());
    }

    public function testTrustedProxiesAffectIpAndSecure(): void
    {
        Request::setTrustedProxies(['192.168.1.0/24']);
        $server = [
            'REMOTE_ADDR' => '192.168.1.5',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];

        $request = new Request('GET', '/', [], [], [], '', $server);

        $this->assertSame('203.0.113.9', $request->ip());
        $this->assertTrue($request->isSecure());
    }

    public function testTrustedProxiesIpv6Cidr(): void
    {
        Request::setTrustedProxies(['2001:db8::/32']);
        $server = [
            'REMOTE_ADDR' => '2001:db8::1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];

        $request = new Request('GET', '/', [], [], [], '', $server);

        $this->assertSame('203.0.113.9', $request->ip());
        $this->assertTrue($request->isSecure());
    }
}
