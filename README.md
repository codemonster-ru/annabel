# Annabel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![License](https://img.shields.io/packagist/l/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![Tests](https://github.com/codemonster-ru/annabel/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/annabel/actions/workflows/tests.yml)

Elegant and lightweight PHP framework for modern web applications.

## Installation

```bash
composer require codemonster-ru/annabel
```

## Quick Start

```php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->run();

// bootstrap/app.php
use Codemonster\Annabel\Application;

$baseDir = __DIR__ . '/..';

$app = new Application($baseDir);

require "$baseDir/routes/web.php";

return $app;

// routes/web.php
router()->get('/', fn() => view('home', ['title' => 'Welcome to Annabel']));
```

## CLI

Annabel ships with a lightweight CLI similar to Laravel's `artisan`. It already supports:

- `about` — show version, base path, and loaded providers
- `route:list` — list registered routes
- `config:get key` — read a config value
- `container:list` — show container bindings/instances
- `serve` — run PHP built-in server (default 127.0.0.1:8000)
- With `codemonster-ru/database` installed: `make:migration`, `migrate`, `migrate:rollback`, `migrate:status` (appear in `annabel list`; connection is checked when commands run)

```bash
php vendor/bin/annabel
php vendor/bin/annabel help
php vendor/bin/annabel help list
```

## Database Integration

Annabel ships with first‑class integration for  
[`codemonster-ru/database`](https://github.com/codemonster-ru/database).

### 1. Create `config/database.php`

```php
return [
    'default' => 'mysql',

    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'database' => env('DB_NAME'),
            'username' => env('DB_USER'),
            'password' => env('DB_PASS'),
            'charset'  => 'utf8mb4',
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => base_path('database/database.sqlite'),
        ],
    ],
];
```

### 2. Usage

```php
// Query builder
$users = db()->table('users')->where('active', 1)->get();

// Schema builder
schema()->create('posts', function ($table) {
    $table->id();
    $table->string('title');
});

// Transactions
transaction(function () {
    db()->table('logs')->insert(['type' => 'created']);
});
```

## Helpers

| Function                | Description                        |
| ----------------------- | ---------------------------------- |
| `app()`                 | Access the application container   |
| `base_path()`           | Resolve base project paths         |
| `config()`              | Get or set configuration values    |
| `env()`                 | Read environment variables         |
| `dump()` / `dd()`       | Debugging utilities                |
| `request()`             | Get current HTTP request           |
| `response()` / `json()` | Create HTTP response               |
| `router()` / `route()`  | Access router instance             |
| `view()`                | Render or return view instance     |
| `session()`             | Access session store               |
| `db()`                  | Get the active database connection |
| `schema()`              | Get the schema builder             |
| `transaction()`         | Execute a DB transaction           |

All helpers are autoloaded automatically.

## Testing

```bash
composer test
```

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/annabel/blob/main/LICENSE)
