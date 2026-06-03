> [!IMPORTANT]
> This repository is read-only.
>
> Development happens in the Annabel monorepo:
> https://github.com/codemonster-ru/annabel
>
> Issues and pull requests should be opened there.

# codemonster-ru/router

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/router.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/router)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/router.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/router)
[![License](https://img.shields.io/packagist/l/codemonster-ru/router.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/router)
[![Tests](https://github.com/codemonster-ru/router/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/router/actions/workflows/tests.yml)

A lightweight router for PHP applications.

## 📦 Installation

```bash
composer require codemonster-ru/router
```

## 🚀 Usage

```php
use Codemonster\Router\Router;

$router = new Router();

$router->get('/', fn() => 'Home Page');
$router->get('/about', fn() => 'About Us');

$result = $router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

if ($result === null) {
    http_response_code(404);

    echo 'Not Found';
} else {
    echo $result;
}
```

## ✨ Features

-   Simple route registration (`get`, `post`, `any`)
-   Support for callbacks, `[Controller::class, 'method']` controllers, and `Controller@method` strings
-   Returns a **pure result**, without binding to a specific `Response`

## 🧪 Testing

```bash
composer test
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/router/blob/main/LICENSE)
