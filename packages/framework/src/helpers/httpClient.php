<?php

use Codemonster\HttpClient\HttpClient;

if (!function_exists('http_client')) {
    function http_client(): HttpClient
    {
        /** @var HttpClient $client */
        $client = app(HttpClient::class);

        return $client;
    }
}
