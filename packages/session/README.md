# codemonster-ru/session

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/session.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/session)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/session.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/session)
[![License](https://img.shields.io/packagist/l/codemonster-ru/session.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/session)
[![Tests](https://github.com/codemonster-ru/session/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/session/actions/workflows/tests.yml)
[![Coverage](https://codecov.io/gh/codemonster-ru/session/branch/main/graph/badge.svg)](https://codecov.io/gh/codemonster-ru/session)

**Lightweight session management library for PHP** - object-oriented.

## Installation

```bash
composer require codemonster-ru/session
```

## Usage

### Basic example

```php
use Codemonster\Session\Session;

// Start session (default: file storage)
Session::start();

// Store values
Session::put('user', 'Vasya');
Session::put('role', 'admin');

// Retrieve values
echo Session::get('user'); // Vasya

// Remove values
Session::forget('role');

// Get all session data
print_r(Session::all());

// Destroy current session
Session::destroy();
```

### Regenerating session ID (fixation protection)

```php
// Rotate ID after login or privilege change
Session::regenerate();
```

### Regenerating on start

```php
// Force new ID at start (keeps data, destroys old session)
Session::start(options: ['regenerate' => true]);
```

### Cookie options

```php
Session::start(options: [
    'cookie' => [
        'secure' => true,
        'samesite' => 'Strict',
        'lifetime' => 3600,
        'path' => '/',
        'domain' => 'example.com'
    ]
]);
```

Notes:

-   If `secure` is not provided, it is set automatically when HTTPS is detected.
-   If `samesite` is `None`, `secure` is forced to `true`.

### Production cookie example

```php
Session::start(options: [
    'cookie' => [
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
        'lifetime' => 60 * 60 * 24,
        'path' => '/'
    ]
]);
```

### Encryption

```php
use Codemonster\Session\Session;

$key = random_bytes(32);

Session::start(options: [
    'encryption' => [
        'key' => $key,
        // Optional: allow decrypting existing plaintext sessions
        // Also triggers auto-migration to encrypted payload on first read
        'allow_plaintext' => true,
        // Optional: previous keys for rotation
        'previous_keys' => []
    ]
]);
```

### Encryption key rotation

```php
$oldKey = random_bytes(32);
$newKey = random_bytes(32);

Session::start(options: [
    'encryption' => [
        'key' => $oldKey,
        'allow_plaintext' => true
    ]
]);

// rotate to a new key, keeping the old key for decryption
Session::rotateEncryptionKey($newKey, [$oldKey]);
```

### Production encryption example

```php
// Store keys in env/secret manager; use base64 for readability.
$currentKey = base64_decode(getenv('SESSION_KEY'), true);
$previousKey = base64_decode(getenv('SESSION_KEY_PREV'), true);

Session::start(options: [
    'encryption' => [
        'key' => $currentKey,
        'previous_keys' => array_filter([$previousKey]),
        'allow_plaintext' => false
    ]
]);
```

### Key rotation strategy

1. Deploy with `previous_keys` containing the old key and `key` as the new key.
2. Keep `allow_plaintext=false` if you already migrated to encryption.
3. After rotation window, remove the old key from `previous_keys`.

### Using Array handler (for tests or CLI)

```php
Session::start('array');
Session::put('debug', true);

echo Session::get('debug'); // true
```

### Handy helpers

```php
Session::put('count', 1);
Session::increment('count', 2); // 3

Session::put('token', 'abc');
Session::has('token'); // true
Session::pull('token'); // 'abc'
Session::has('token'); // false

Session::put('a', 1);
Session::put('b', 2);
Session::forgetMany(['a', 'b']);

// Flash data lasts for the next start and is then removed
Session::flash('notice', 'Saved');
```

### TTL for keys

```php
Session::putWithTtl('token', 'abc', 60); // expires in 60 seconds
Session::sweepExpired(); // optional manual cleanup
Session::ttl('token'); // remaining seconds
Session::expiresAt('token'); // unix timestamp
Session::touch('token', 120); // extend TTL
```

### Namespaced sessions

```php
$admin = Session::for('admin');
$user = Session::for('user');

$admin->put('token', 'admin123');
$user->put('token', 'user456');
```

### Namespace helpers

```php
$admin = Session::for('admin');
$admin->keys(); // ['token', ...]
$admin->forgetNamespace();
Session::count(); // count of user keys
Session::touchAll(120); // extend TTL for all keys
Session::touchAll(120, 'admin.'); // extend only for namespace

Session::keys(); // ['admin.token', 'user.token', ...]
Session::forgetNamespace('user');
```

### Custom namespace delimiter

```php
$scope = Session::for('admin', ':');
$scope->put('token', 'x'); // stored as admin:token
```

### Debug helpers

```php
Session::dump(); // array of user-visible keys/values
Session::dump(['token']); // redact selected keys
Session::dump([], ['user*']); // redact by pattern
Session::size(); // payload size in bytes
```

### Key helpers

```php
Session::keys(); // all keys
Session::keysMatch('user*'); // wildcard match
```

### Session manager (non-static)

```php
$manager = Session::manager();
$manager->put('token', 'abc');

$scoped = $manager->for('admin');
$scoped->put('token', 'admin123');
```

### Using custom handler

```php
use Codemonster\Session\Session;
use App\Session\RedisSessionHandler;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$handler = new RedisSessionHandler($redis);

Session::start(customHandler: $handler);
Session::put('user_id', 42);
```

### Using Redis handler

```php
use Codemonster\Session\Handlers\RedisSessionHandler;
use Codemonster\Session\Session;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$handler = new RedisSessionHandler($redis, prefix: 'sess_', ttl: 3600, retries: 2, retryDelayMs: 100);

Session::start(customHandler: $handler);
```

### Using Redis Cluster handler

```php
use Codemonster\Session\Handlers\RedisClusterSessionHandler;
use Codemonster\Session\Session;

$cluster = new RedisCluster(null, ['127.0.0.1:7000', '127.0.0.1:7001']);

$handler = new RedisClusterSessionHandler($cluster, prefix: 'sess_', ttl: 3600, retries: 2, retryDelayMs: 100);

Session::start(customHandler: $handler);
```

### Using Redis Sentinel handler

```php
use Codemonster\Session\Handlers\RedisSentinelSessionHandler;
use Codemonster\Session\Session;

$sentinel = new RedisSentinel('127.0.0.1', 26379);

$handler = new RedisSentinelSessionHandler(
    $sentinel,
    service: 'mymaster',
    prefix: 'sess_',
    ttl: 3600,
    retries: 2,
    retryDelayMs: 100
);

Session::start(customHandler: $handler);
```

### Using Predis handler

```php
use Codemonster\Session\Handlers\PredisSessionHandler;
use Codemonster\Session\Session;
use Predis\Client;

$client = new Client('tcp://127.0.0.1:6379');

$handler = new PredisSessionHandler($client, prefix: 'sess_', ttl: 3600, retries: 2, retryDelayMs: 100);

Session::start(customHandler: $handler);
```

### Using PSR-16 cache handler

```php
use Codemonster\Session\Handlers\CacheSessionHandler;
use Codemonster\Session\Session;
use Psr\SimpleCache\CacheInterface;

/** @var CacheInterface $cache */
$handler = new CacheSessionHandler($cache, prefix: 'sess_', ttl: 3600, retries: 2, retryDelayMs: 100);

Session::start(customHandler: $handler);
```

## Testing

You can run tests with the command:

```bash
composer test
```

Static analysis:

```bash
composer analyse
composer psalm
```

Redis integration tests (optional):

```bash
REDIS_TESTS=1 composer test
```

Redis Sentinel/Cluster integration tests (optional):

```bash
REDIS_SENTINEL_TESTS=1 composer test
REDIS_CLUSTER_TESTS=1 composer test
```

Stress test (optional):

```bash
composer stress
composer stress -- 20000 array
composer stress -- 20000 file
REDIS_HOST=127.0.0.1 REDIS_PORT=6379 composer stress -- 20000 redis

# With thresholds: iterations, driver, min_ops, max_seconds
composer stress -- 20000 array 3400 6
composer stress -- 10000 file 1700 6
composer stress -- 20000 redis 400 6
```

Note: thresholds depend on runner performance; recalibrate if CI hardware changes.

## Security

Security reports: email admin@codemonster.net with a clear description and steps to reproduce.

Security checklist:

-   Use HTTPS and `secure` cookies in production.
-   Set `SameSite` to `Strict` or `Lax` based on your flow.
-   Use `httponly` to reduce XSS access to cookies.
-   Rotate session IDs after login or privilege changes.
-   Consider payload encryption (`encryption.key`) for sensitive data.
-   Keep session storage private and with proper file permissions.

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/session/blob/main/LICENSE)
