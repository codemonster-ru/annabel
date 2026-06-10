# Codemonster Logging

PSR-3 logging primitives for Annabel applications.

## Usage

```php
use Codemonster\Logging\LoggerManager;

$manager = new LoggerManager([
    'default' => 'file',
    'channels' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/storage/logs/app.log',
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
]);

$manager->channel()->info('User registered', [
    'user_id' => 42,
]);
```

The package ships with `file` and `null` channels and implements PSR-3 through
`Psr\Log\LoggerInterface`.
