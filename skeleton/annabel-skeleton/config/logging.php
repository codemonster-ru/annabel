<?php

$logFile = env('LOG_FILE');

return [
    'default' => env('LOG_CHANNEL', 'file'),
    'channels' => [
        'file' => [
            'path' => is_string($logFile) && $logFile !== ''
                ? $logFile
                : base_path('storage/logs/annabel.log'),
        ],
    ],
];
