<?php

namespace Codemonster\Annabel\Testing;

use Codemonster\Annabel\Application;
use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\GuardInterface;
use Codemonster\Http\Request;

trait InteractsWithApplication
{
    protected ?Application $annabelApplication = null;

    abstract protected function createApplication(): Application;

    protected function app(): Application
    {
        return $this->annabelApplication ??= $this->createApplication();
    }

    protected function refreshApplication(): Application
    {
        Application::resetInstance();

        return $this->annabelApplication = $this->createApplication();
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function get(string $uri, array $headers = []): TestResponse
    {
        return $this->call('GET', $uri, [], $headers);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $data, $headers);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function json(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $headers);

        $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            throw new \RuntimeException('Unable to encode JSON request body.');
        }

        return $this->call($method, $uri, $data, $headers, $body);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function call(
        string $method,
        string $uri,
        array $data = [],
        array $headers = [],
        string $rawBody = '',
    ): TestResponse {
        $request = new Request(strtoupper($method), $uri, body: $data, headers: $headers, rawBody: $rawBody);

        return new TestResponse($this->app()->getKernel()->handle($request));
    }

    protected function actingAs(AuthenticatableInterface $user): static
    {
        $guard = $this->app()->make(GuardInterface::class);
        $guard->login($user);

        return $this;
    }
}
