<?php

namespace Codemonster\Auth\Middleware;

use Codemonster\Auth\Contracts\AuthorizerInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class Authorize
{
    public function __construct(protected AuthorizerInterface $authorizer)
    {
    }

    public function handle(Request $request, callable $next, ?string $arguments = null): mixed
    {
        [$ability, $argumentNames] = $this->parseArguments($arguments);
        $routeParameters = $this->routeParameters($request);

        $resolved = [];
        foreach ($argumentNames as $name) {
            if (!array_key_exists($name, $routeParameters)) {
                throw new \InvalidArgumentException("Route parameter [{$name}] is required for authorization.");
            }

            $resolved[] = $routeParameters[$name];
        }

        if ($this->authorizer->denies($ability, ...$resolved)) {
            return $request->wantsJson()
                ? Response::json(['message' => 'Forbidden.'], 403)
                : new Response('Forbidden.', 403);
        }

        return $next($request);
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function parseArguments(?string $arguments): array
    {
        $parts = array_values(array_filter(
            array_map('trim', explode(',', $arguments ?? '')),
            fn (string $part): bool => $part !== '',
        ));

        $ability = array_shift($parts);
        if ($ability === null) {
            throw new \InvalidArgumentException('Authorization ability is required.');
        }

        return [$ability, $parts];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameters(Request $request): array
    {
        $parameters = $request->getAttribute('route.parameters', []);

        if (!is_array($parameters)) {
            return [];
        }

        $normalized = [];
        foreach ($parameters as $name => $value) {
            if (is_string($name)) {
                $normalized[$name] = $value;
            }
        }

        return $normalized;
    }
}
