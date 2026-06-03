<?php

namespace {
    if (!function_exists('app')) {
        function app(?string $abstract = null, array $parameters = []): mixed
        {
            return TestApp::make($abstract);
        }
    }

    class TestApp
    {
        private static ?TestConfigRepository $config = null;

        public static function make(?string $abstract): mixed
        {
            if ($abstract !== 'config') {
                return null;
            }

            if (!self::$config) {
                self::$config = new TestConfigRepository();
            }

            return self::$config;
        }
    }

    class TestConfigRepository
    {
        private static array $data = [];

        public function all(): array
        {
            return self::$data;
        }

        public function set(string $key, mixed $value): void
        {
            $parts = explode('.', $key);
            $cursor = &self::$data;

            foreach ($parts as $part) {
                if (!isset($cursor[$part]) || !is_array($cursor[$part])) {
                    $cursor[$part] = [];
                }

                $cursor = &$cursor[$part];
            }

            $cursor = $value;
        }

        public function get(string $key, mixed $default = null): mixed
        {
            $value = self::$data;

            foreach (explode('.', $key) as $part) {
                if (!is_array($value) || !array_key_exists($part, $value)) {
                    return $default;
                }

                $value = $value[$part];
            }

            return $value;
        }

        public static function reset(): void
        {
            self::$data = [];
        }
    }
}

namespace Codemonster\Security\Tests\RateLimiting {

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
                ['REMOTE_ADDR' => '10.0.0.2']
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
                ['REMOTE_ADDR' => '10.0.0.1']
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
                ['REMOTE_ADDR' => '2001:db8::1']
            );

            $middleware = new TestableThrottleRequests(null, 60, 60, [], ['2001:db8::/32']);

            $this->assertSame('2001:db8::1234', $middleware->publicRequestIp($request));
        }

        public function testExceptPathSkipsThrottle(): void
        {
            $limiter = new RateLimiter(new SessionThrottleStorage());
            $middleware = new ThrottleRequests($limiter, 1, 60, ['api/*']);

            $request = new Request('POST', '/api/login');

            $response = $middleware->handle($request, fn() => new Response('ok'));

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

            \TestConfigRepository::reset();
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

            $this->assertSame(200, $middleware->handle($request, fn() => new Response('ok'), 'login')->getStatusCode());
            $this->assertSame(429, $middleware->handle($request, fn() => new Response('ok'), 'login')->getStatusCode());

            \TestConfigRepository::reset();
        }

        public function testAddsRateLimitHeaders(): void
        {
            $middleware = new ThrottleRequests(new RateLimiter(new SessionThrottleStorage()), 5, 60);
            $request = new Request('POST', '/login');

            $response = $middleware->handle($request, fn() => new Response('ok'));
            $headers = $response->getHeaders();

            $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
            $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Limit', $headers);
            $this->assertArrayHasKey('RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Reset', $headers);
            $this->assertIsString($headers['RateLimit-Reset']);
        }

        public function testAddsRateLimitHeadersOnThrottleResponse(): void
        {
            $middleware = new ThrottleRequests(new RateLimiter(new SessionThrottleStorage()), 1, 60);
            $request = new Request('POST', '/login');

            $this->assertSame(200, $middleware->handle($request, fn() => new Response('ok'))->getStatusCode());

            $response = $middleware->handle($request, fn() => new Response('ok'));
            $this->assertSame(429, $response->getStatusCode());

            $headers = $response->getHeaders();

            $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
            $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Limit', $headers);
            $this->assertArrayHasKey('RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('RateLimit-Reset', $headers);
            $this->assertIsString($headers['RateLimit-Reset']);
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

        public function publicResolveLimits(mixed $role): array
        {
            return $this->resolveLimits($role);
        }
    }
}
