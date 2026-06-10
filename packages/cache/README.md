# Codemonster Cache

PSR-16 cache stores for Annabel applications.

## Usage

```php
use Codemonster\Cache\CacheManager;

$cache = new CacheManager([
    'default' => 'file',
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/storage/cache',
        ],
        'redis' => [
            'driver' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'prefix' => 'cache:',
        ],
        'array' => [
            'driver' => 'array',
        ],
    ],
]);

$cache->store()->set('name', 'annabel', 60);

echo $cache->store()->get('name');
```

The package ships with `array`, `file`, and `redis` stores and implements
`Psr\SimpleCache\CacheInterface`. The Redis store uses the PHP Redis extension
when no explicit client object is configured.
