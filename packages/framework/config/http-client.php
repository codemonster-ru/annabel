<?php

return [
    'base_url' => env('HTTP_CLIENT_BASE_URL', ''),
    'timeout' => (float) env('HTTP_CLIENT_TIMEOUT', 30),
    'headers' => [],
];
