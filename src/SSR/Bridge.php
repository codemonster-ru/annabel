<?php

namespace Annabel\SSR;

class Bridge
{
    protected string $url;

    public function __construct(string $url = "http://127.0.0.1:3001")
    {
        $this->url = $url;
    }

    public function render(string $component, array $props = []): string
    {
        $payload = json_encode([
            'component' => $component,
            'props' => $props,
        ]);

        $ch = curl_init("$this->url/render");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException("Error requesting Node.js: " . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
}
