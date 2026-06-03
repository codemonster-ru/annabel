<?php

namespace Codemonster\Security\Csrf;

use Codemonster\Http\Request;
use Codemonster\Http\Response;

class VerifyCsrfToken
{
    protected CsrfTokenManager $tokens;
    protected array $exceptPaths;
    protected array $exceptMethods;
    protected bool $verifyJsonRequests;
    protected string $inputKey;

    public function __construct(
        ?CsrfTokenManager $tokens = null,
        array $exceptPaths = [],
        array $exceptMethods = ['GET', 'HEAD', 'OPTIONS'],
        bool $verifyJsonRequests = false,
        string $inputKey = '_token'
    ) {
        $this->tokens = $tokens ?? new CsrfTokenManager();
        $this->exceptPaths = $exceptPaths;
        $this->exceptMethods = array_map('strtoupper', $exceptMethods);
        $this->verifyJsonRequests = $verifyJsonRequests;
        $this->inputKey = $inputKey;
    }

    public function handle(Request $request, callable $next, mixed $role = null): mixed
    {
        if ($this->shouldSkip($request)) {
            $this->tokens->token();

            return $next($request);
        }

        $provided = $this->extractToken($request);

        if (!$this->tokens->validate($provided)) {
            return $this->errorResponse($request);
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        if (in_array(strtoupper($request->method()), $this->exceptMethods, true)) {
            return true;
        }

        if (!$this->verifyJsonRequests && $request->wantsJson()) {
            return true;
        }

        return $this->inExceptPaths($request->uri());
    }

    protected function inExceptPaths(string $uri): bool
    {
        $path = ltrim($uri, '/');

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

    protected function extractToken(Request $request): ?string
    {
        $token = $request->input($this->inputKey);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $header = $request->header('X-CSRF-TOKEN');

        if (is_string($header) && $header !== '') {
            return $header;
        }

        $header = $request->header('X-XSRF-TOKEN');

        if (is_string($header) && $header !== '') {
            return $header;
        }

        return null;
    }

    protected function errorResponse(Request $request): Response
    {
        $message = 'CSRF token mismatch.';

        if ($request->wantsJson()) {
            return Response::json(['message' => $message], 419);
        }

        return new Response($message, 419, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
