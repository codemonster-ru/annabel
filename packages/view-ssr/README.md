# codemonster-ru/view-ssr

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/view-ssr.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view-ssr)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/view-ssr.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view-ssr)
[![License](https://img.shields.io/packagist/l/codemonster-ru/view-ssr.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view-ssr)
[![Tests](https://github.com/codemonster-ru/view-ssr/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/view-ssr/actions/workflows/tests.yml)

SSR (Server-Side Rendering) engine for the [`codemonster-ru/view`](https://github.com/codemonster-ru/view) core.
Delegates actual rendering to a **bridge** (preferably `codemonster-ru/ssr-bridge`).

## 📦 Installation

Via Composer:

```bash
composer require codemonster-ru/view-ssr
```

## 🚀 Usage

```php
use Codemonster\View\View;
use Codemonster\View\Engines\SsrEngine;
use Codemonster\Ssr\SsrBridge; // from codemonster-ru/ssr-bridge

$bridge = new SsrBridge([
  // ...bridge-specific options: mode, entry paths, server URL, etc.
]);

$engine = new SsrEngine($bridge);
$view   = new View(['ssr' => $engine], 'ssr');

echo $view->render('home', ['message' => 'Hello']);
```

## 🧪 Testing

You can run tests with the command:

```bash
composer test
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/view-ssr/blob/main/LICENSE)
