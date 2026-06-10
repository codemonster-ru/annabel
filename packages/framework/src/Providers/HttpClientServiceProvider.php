<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\HttpClient\HttpClient;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/http-client.php' => $this->app()->getBasePath() . '/config/http-client.php',
        ], ['config', 'http-client']);

        $this->app()->singleton(HttpClient::class, fn (): HttpClient => $this->client());
        $this->app()->singleton('http.client', fn (): HttpClient => $this->client());
    }

    private function client(): HttpClient
    {
        $config = $this->httpClientConfig();
        $client = new HttpClient();

        $baseUrl = $config['base_url'] ?? null;
        if (is_string($baseUrl) && $baseUrl !== '') {
            $client = $client->baseUrl($baseUrl);
        }

        $timeout = $config['timeout'] ?? null;
        if (is_int($timeout) || is_float($timeout)) {
            $client = $client->timeout((float) $timeout);
        }

        $headers = $config['headers'] ?? null;
        if (is_array($headers)) {
            $normalized = [];
            foreach ($headers as $name => $value) {
                if (is_string($name) && is_string($value)) {
                    $normalized[$name] = $value;
                }
            }
            $client = $client->withHeaders($normalized);
        }

        return $client;
    }

    /**
     * @return array<string, mixed>
     */
    private function httpClientConfig(): array
    {
        $config = config('http-client', [
            'base_url' => '',
            'timeout' => 30,
            'headers' => [],
        ]);

        if (!is_array($config)) {
            throw new \RuntimeException('HTTP client config must be an array.');
        }

        $normalized = [];
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
