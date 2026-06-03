# SSR Bridge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/ssr-bridge.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/ssr-bridge)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/ssr-bridge.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/ssr-bridge)
[![License](https://img.shields.io/packagist/l/codemonster-ru/ssr-bridge.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/ssr-bridge)
[![Tests](https://github.com/codemonster-ru/ssr-bridge/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/ssr-bridge/actions/workflows/tests.yml)

A universal PHP bridge for interacting with **Node.js SSR services**.

## 📦 Installation

```bash
composer require codemonster-ru/ssr-bridge
```

## 🚀 Usage

```php
use Codemonster\Ssr\SsrBridge;

// Local mode (Node.js runs directly)
$bridge = new SsrBridge('local', null, __DIR__.'/../node_modules/ssr-service/dist/ssr.js');
$html = $bridge->render('Home', ['message' => 'Hello']);

// HTTP mode (connecting to the remote SSR API)
$bridge = new SsrBridge('http', 'http://127.0.0.1:3000');
$html = $bridge->render('Home', ['message' => 'Hello']);
```

## ✨ Features

-   Local SSR execution via the `node` process
-   Connection to a remote HTTP SSR server API
-   Easy integration into any PHP project (Laravel, Symfony, Annabel, etc.)

## 🧪 Testing

You can run tests with the command:

```bash
composer test
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/ssr-bridge/blob/main/LICENSE)
