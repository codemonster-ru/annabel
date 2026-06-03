<?php

namespace Codemonster\Security\RateLimiting;

use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Security\RateLimiting\Contracts\AttemptRateLimiterInterface;
use Codemonster\Security\RateLimiting\Contracts\RateLimiterInterface;
use Codemonster\Security\RateLimiting\Storage\SessionThrottleStorage;

class ThrottleRequests
{
    protected RateLimiterInterface $limiter;
    protected int $maxAttempts;
    protected int $decaySeconds;
    protected array $exceptPaths;
    protected array $trustedProxies;

    public function __construct(
        ?RateLimiterInterface $limiter = null,
        int $maxAttempts = 60,
        int $decaySeconds = 60,
        array $exceptPaths = [],
        array $trustedProxies = []
    ) {
        $this->limiter = $limiter ?? new RateLimiter(new SessionThrottleStorage());
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->exceptPaths = $exceptPaths;
        $this->trustedProxies = $trustedProxies;
    }

    public function handle(Request $request, callable $next, mixed $role = null): mixed
    {
        if ($this->inExceptPaths($request->uri())) {
            return $next($request);
        }

        $preset = is_string($role) ? $this->resolvePresetConfig($role) : null;

        if ($preset !== null && is_array($preset['account'] ?? null)) {
            $ipPreset = is_array($preset['ip'] ?? null) ? $preset['ip'] : $preset;
            $ipLimits = $this->presetLimitsFromConfig($ipPreset) ?? [$this->maxAttempts, $this->decaySeconds];
            [$ipMaxAttempts, $ipDecaySeconds] = $ipLimits;

            $ipKey = $this->resolveKey($request, null);
            $ipResult = $this->attemptKey($ipKey, $ipMaxAttempts, $ipDecaySeconds);

            if ($ipResult['limited']) {
                return $this->buildThrottleResponse($request, $ipKey, $ipMaxAttempts);
            }

            $accountLimits = $this->presetLimitsFromConfig($preset['account']);
            $accountField = (string) ($preset['account']['field'] ?? 'email');
            $accountKey = $this->resolveAccountKey($request, $accountField);

            if ($accountKey !== null && $accountLimits !== null) {
                [$accountMax, $accountDecay] = $accountLimits;
                $accountResult = $this->attemptKey($accountKey, $accountMax, $accountDecay);

                if ($accountResult['limited']) {
                    return $this->buildThrottleResponse($request, $accountKey, $accountMax);
                }
            }

            $response = $next($request);

            if ($response instanceof Response) {
                $resetAt = time() + $this->limiter->availableIn($ipKey);
                $response->header('X-RateLimit-Limit', (string) $ipMaxAttempts);
                $response->header('X-RateLimit-Remaining', (string) $ipResult['remaining']);
                $response->header('RateLimit-Limit', (string) $ipMaxAttempts);
                $response->header('RateLimit-Remaining', (string) $ipResult['remaining']);
                $response->header('RateLimit-Reset', (string) $resetAt);
            }

            return $response;
        }

        [$maxAttempts, $decaySeconds] = $this->resolveLimits($role);
        $key = $this->resolveKey($request, $role);

        $result = $this->attemptKey($key, $maxAttempts, $decaySeconds);

        if ($result['limited']) {
            return $this->buildThrottleResponse($request, $key, $maxAttempts);
        }

        $response = $next($request);

        if ($response instanceof Response) {
            $resetAt = time() + $this->limiter->availableIn($key);
            $response->header('X-RateLimit-Limit', (string) $maxAttempts);
            $response->header('X-RateLimit-Remaining', (string) $result['remaining']);
            $response->header('RateLimit-Limit', (string) $maxAttempts);
            $response->header('RateLimit-Remaining', (string) $result['remaining']);
            $response->header('RateLimit-Reset', (string) $resetAt);
        }

        return $response;
    }

    protected function resolveLimits(mixed $role): array
    {
        if (is_string($role)) {
            if (preg_match('/^\s*(\d+)\s*,\s*(\d+)\s*$/', $role, $m)) {
                return [(int) $m[1], (int) $m[2]];
            }

            $preset = $this->resolvePresetConfig($role);

            if ($preset !== null) {
                $limits = $this->presetLimitsFromConfig($preset);

                if ($limits !== null) {
                    return $limits;
                }
            }
        }

        return [$this->maxAttempts, $this->decaySeconds];
    }

    protected function resolvePresetConfig(string $name): ?array
    {
        if (!function_exists('config')) {
            return null;
        }

        try {
            $presets = config('security.throttle.presets', []);
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_array($presets) || !isset($presets[$name]) || !is_array($presets[$name])) {
            return null;
        }

        return $presets[$name];
    }

    protected function presetLimitsFromConfig(array $preset): ?array
    {
        $maxAttempts = (int) ($preset['max_attempts'] ?? $preset['max'] ?? 0);
        $decaySeconds = (int) ($preset['decay_seconds'] ?? $preset['decay'] ?? 0);

        if ($maxAttempts <= 0 || $decaySeconds <= 0) {
            return null;
        }

        return [$maxAttempts, $decaySeconds];
    }

    protected function resolveKey(Request $request, mixed $role): string
    {
        if (is_callable($role)) {
            $key = $role($request);

            if (is_string($key) && $key !== '') {
                return $key;
            }
        }

        $ip = $this->requestIp($request);
        $identity = $ip . '|' . $request->method() . '|' . $this->requestPath($request);

        return sha1($identity);
    }

    protected function requestIp(Request $request): string
    {
        if (!$this->isFromTrustedProxy($request)) {
            return $this->serverRemoteAddr($request);
        }

        $xff = $request->header('X-Forwarded-For');

        if (is_string($xff) && $xff !== '') {
            $parts = array_map('trim', explode(',', $xff));

            if (!empty($parts[0])) {
                return $parts[0];
            }
        }

        $realIp = $request->header('X-Real-IP');

        if (is_string($realIp) && $realIp !== '') {
            return trim($realIp);
        }

        return $this->serverRemoteAddr($request);
    }

    protected function serverRemoteAddr(Request $request): string
    {
        if (method_exists($request, 'server')) {
            $server = $request->server();

            return (string) ($server['REMOTE_ADDR'] ?? '0.0.0.0');
        }

        $all = $request->all();
        $server = is_array($all['server'] ?? null) ? $all['server'] : [];

        return (string) ($server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    protected function isFromTrustedProxy(Request $request): bool
    {
        $remote = $this->serverRemoteAddr($request);

        if ($remote === '') {
            return false;
        }

        if (in_array($remote, $this->trustedProxies, true)) {
            return true;
        }

        foreach ($this->trustedProxies as $proxy) {
            if (is_string($proxy) && $proxy !== '' && str_contains($proxy, '/')) {
                if ($this->ipInCidr($remote, $proxy)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $maskBits] = array_pad(explode('/', $cidr, 2), 2, null);

        if (!is_string($subnet) || $subnet === '' || !is_string($maskBits)) {
            return false;
        }

        $maskBits = (int) $maskBits;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $maskBits = max(0, min(128, $maskBits));
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);

            if ($ipBin === false || $subnetBin === false) {
                return false;
            }

            $bytes = intdiv($maskBits, 8);
            $bits = $maskBits % 8;

            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }

            if ($bits === 0) {
                return true;
            }

            $maskByte = (0xFF << (8 - $bits)) & 0xFF;
            $ipByte = ord($ipBin[$bytes]);
            $subnetByte = ord($subnetBin[$bytes]);

            return (($ipByte & $maskByte) === ($subnetByte & $maskByte));
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        if ($maskBits <= 0) {
            return true;
        }

        if ($maskBits > 32) {
            return false;
        }

        $mask = ~((1 << (32 - $maskBits)) - 1);

        return (($ipLong & $mask) === ($subnetLong & $mask));
    }

    protected function requestPath(Request $request): string
    {
        $uri = $request->uri();
        $path = parse_url($uri, PHP_URL_PATH);

        return (is_string($path) && $path !== '') ? $path : $uri;
    }

    protected function attemptKey(string $key, int $maxAttempts, int $decaySeconds): array
    {
        $remaining = $maxAttempts;
        $limited = false;

        if ($this->limiter instanceof AttemptRateLimiterInterface) {
            $result = $this->limiter->attempt($key, $maxAttempts, $decaySeconds);
            $remaining = (int) ($result['remaining'] ?? 0);
            $limited = (bool) ($result['limited'] ?? false);
        } else {
            if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
                $limited = true;
            } else {
                $attempts = $this->limiter->hit($key, $decaySeconds);
                $remaining = max(0, $maxAttempts - $attempts);
            }
        }

        return ['remaining' => $remaining, 'limited' => $limited];
    }

    protected function resolveAccountKey(Request $request, string $field): ?string
    {
        $value = $request->input($field);

        if (!is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        $identity = $value . '|' . $request->method() . '|' . $this->requestPath($request);

        return 'acct:' . sha1($identity);
    }

    protected function buildThrottleResponse(Request $request, string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);
        $resetAt = time() + $retryAfter;
        $headers = [
            'Retry-After' => (string) $retryAfter,
            'X-RateLimit-Limit' => (string) $maxAttempts,
            'X-RateLimit-Remaining' => '0',
            'RateLimit-Limit' => (string) $maxAttempts,
            'RateLimit-Remaining' => '0',
            'RateLimit-Reset' => (string) $resetAt,
        ];

        if ($request->wantsJson()) {
            return Response::json(['message' => 'Too Many Requests'], 429, $headers);
        }

        return new Response('Too Many Requests', 429, array_merge(['Content-Type' => 'text/plain; charset=utf-8'], $headers));
    }

    protected function inExceptPaths(string $uri): bool
    {
        $path = ltrim((string) parse_url($uri, PHP_URL_PATH), '/');

        foreach ($this->exceptPaths as $pattern) {
            $pattern = ltrim((string) $pattern, '/');

            if ($pattern === '') {
                continue;
            }

            if ($pattern === $path) {
                return true;
            }

            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';

                if (preg_match($regex, $path) === 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
