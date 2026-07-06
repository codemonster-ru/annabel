<?php

declare(strict_types=1);

namespace {
    if (!function_exists('app')) {
        /** @param array<string, mixed> $parameters */
        function app(?string $abstract = null, array $parameters = []): mixed
        {
            return TestApp::make($abstract);
        }
    }

    class TestApp
    {
        private static ?\Codemonster\Config\Config $config = null;

        public static function make(?string $abstract): mixed
        {
            if ($abstract !== 'config') {
                return null;
            }

            if (!self::$config) {
                self::$config = new \Codemonster\Config\Config();
            }

            return self::$config;
        }
    }
}

namespace Codemonster\Security\Tests\RateLimiting {

    use Codemonster\Config\Config;
    use Codemonster\Http\Request;
    use Codemonster\Http\Response;
    use Codemonster\Security\RateLimiting\RateLimiter;
    use Codemonster\Security\RateLimiting\Storage\SessionThrottleStorage;
    use Codemonster\Security\RateLimiting\ThrottleRequests;
    use Codemonster\Session\Session;
    use PHPUnit\Framework\TestCase;

    class ThrottleRequestsTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            Session::start('array');
        }

        public function testUsesRemoteAddrWhenProxyUntrusted(): void
        {
            $request = new Request(
                'POST',
                '/login',
                [],
                [],
                ['X-Forwarded-For' => '203.0.113.5'],
                '',
                ['REMOTE_ADDR' => '10.0.0.2'],
            );

            $middleware = new TestableThrottleRequests(null, 60, 60, [], ['10.0.0.1']);

            $this->assertSame('10.0.0.2', $middleware->publicRequestIp($request));
        }

        public function testUsesXForwardedForWhenProxyTrusted(): void
        {
            $request = new Request(
                'POST',
                '/login',
                [],
                [],
                ['X-Forwarded-For' => '203.0.113.5, 10.0.0.1'],
                '',
                ['REMOTE_ADDR' => '10.0.0.1'],
            );

            $middleware = new TestableThrottleRequests(null, 60, 60, [], ['10.0.0.1']);

            $this->assertSame('203.0.113.5', $middleware->publicRequestIp($request));
        }

        public function testUsesXForwardedForWhenProxyTrustedByIpv6Cidr(): void
        {
            $request = new Request(
                'POST',
                '/login',
                [],
                [],
                ['X-Forwarded-For' => '2001:db8::1234, 2001:db8::1'],
                '',
                ['REMOTE_ADDR' => '2001:db8::1'],
            );

            $middleware = new TestableThrottleRequests(null, 60, 60, [], ['2001:db8::/32']);

            $this->assertSame('2001:db8::1234', $middleware->publicRequestIp($request));
        }

        public function testExceptPathSkipsThrottle(): void
        {
            $limiter = new RateLimiter(new SessionThrottleStorage());
            $middleware = new ThrottleRequests($limiter, 1, 60, ['api/*']);

            $request = new Request('POST', '/api/login');

            $response = $middleware->handle($request, fn () => new Response('ok'));
            $response = $this->assertResponse($response);

            $this->assertSame(200, $response->getStatusCode());
        }

        public function testResolvesPresetLimitsFromConfig(): void
        {
            config([
                'security.throttle.presets' => [
                    'login' => [
                        'max_attempts' => 5,
                        'decay_seconds' => 60,
                    ],
                ],
            ]);

            $middleware = new TestableThrottleRequests(null, 60, 60, [], []);

            $this->assertSame([5, 60], $middleware->publicResolveLimits('login'));

            Config::reset();
        }

        public function testLoginPresetAppliesAccountLimit(): void
        {
            config([
                'security.throttle.presets' => [
                    'login' => [
                        'ip' => [
                            'max_attempts' => 10,
                            'decay_seconds' => 60,
                        ],
                        'account' => [
                            'max_attempts' => 1,
                            'decay_seconds' => 60,
                            'field' => 'email',
                        ],
                    ],
                ],
            ]);

            $middleware = new ThrottleRequests(new RateLimiter(new SessionThrottleStorage()));
            $request = new Request('POST', '/login', [], ['email' => 'user@example.com']);

            $first = $this->assertResponse($middleware->handle($request, fn () => new Response('ok'), 'login'));
            $second = $this->assertResponse($middleware->handle($request, fn () => new Response('ok'), 'login'));

            $this->assertSame(200, $first->getStatusCode());
            $this->assertSame(429, $second->getStatusCode());

            Config::reset();
        }

        public function testAddsRateLimitHeaders(): void
        {
            $middleware = new ThrottleRequests(new RateLimiter(new SessionThrottleStorage()), 5, 60);
            $request = new Request('POST', '/login');

            $response = $middleware->handle($request, fn () => new Response('ok'));
            $response = $this->assertResponse($response);
            $headers = $this->headers($response);

            $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
            $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Limit', $headers);
            $this->assertArrayHasKey('RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Reset', $headers);
            $this->assertNotSame('', $this->headerLine($headers, 'RateLimit-Reset'));
        }

        public function testAddsRateLimitHeadersOnThrottleResponse(): void
        {
            $middleware = new ThrottleRequests(new RateLimiter(new SessionThrottleStorage()), 1, 60);
            $request = new Request('POST', '/login');

            $first = $this->assertResponse($middleware->handle($request, fn () => new Response('ok')));
            $this->assertSame(200, $first->getStatusCode());

            $response = $middleware->handle($request, fn () => new Response('ok'));
            $response = $this->assertResponse($response);
            $this->assertSame(429, $response->getStatusCode());

            $headers = $this->headers($response);

            $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
            $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Limit', $headers);
            $this->assertArrayHasKey('RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Reset', $headers);
            $this->assertNotSame('', $this->headerLine($headers, 'RateLimit-Reset'));
        }

        private function assertResponse(mixed $response): Response
        {
            $this->assertInstanceOf(Response::class, $response);

            return $response;
        }

        /** @return array<string, list<string>> */
        private function headers(Response $response): array
        {
            $headers = $response->getHeaders();
            $typedHeaders = [];

            foreach ($headers as $name => $values) {
                $this->assertIsString($name);

                if (is_string($values)) {
                    $typedHeaders[$name] = [$values];

                    continue;
                }

                if (is_array($values)) {
                    $typedHeaders[$name] = [];
                    foreach ($values as $value) {
                        $this->assertIsString($value);
                        $typedHeaders[$name][] = $value;
                    }
                }
            }

            return $typedHeaders;
        }

        /** @param array<string, list<string>> $headers */
        private function headerLine(array $headers, string $name): string
        {
            $value = $headers[$name][0] ?? null;
            $this->assertIsString($value);

            return $value;
        }
    }

    class TestableThrottleRequests extends ThrottleRequests
    {
        public function publicRequestIp(Request $request): string
        {
            return $this->requestIp($request);
        }

        public function publicResolveKey(Request $request, mixed $role): string
        {
            return $this->resolveKey($request, $role);
        }

        /** @return array{int, int} */
        public function publicResolveLimits(mixed $role): array
        {
            return $this->resolveLimits($role);
        }
    }
}
