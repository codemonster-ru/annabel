# Annabel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![License](https://img.shields.io/packagist/l/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![Tests](https://github.com/codemonster-ru/annabel/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/annabel/actions/workflows/tests.yml)

Elegant and lightweight PHP framework for modern web applications.

## 📦 Installation

```bash
composer require codemonster-ru/annabel
```

## 🚀 Quick Start

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

## 🧩 Helpers

| Function                | Description                          |
| ----------------------- | ------------------------------------ |
| `app()`                 | Access the application container     |
| `base_path()`           | Resolve base project paths           |
| `config()`              | Get or set configuration values      |
| `dump()` / `dd()`       | Debugging utilities                  |
| `env()`                 | Read environment variables           |
| `request()`             | Get current HTTP request             |
| `response()`/ `json()`  | Create HTTP response                 |
| `router()` / `route()`  | Access router instance               |
| `session()`/ `render()` | Read, write, or access session store |
| `view()`                | Render or return view instance       |

## 🧪 Testing

You can run tests with the command:

```bash
composer test
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/annabel/blob/main/LICENSE)
