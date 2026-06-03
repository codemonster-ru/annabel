> [!IMPORTANT]
> This repository is read-only.
>
> Development happens in the Annabel monorepo:
> https://github.com/codemonster-ru/annabel
>
> Issues and pull requests should be opened there.

# codemonster-ru/view-php

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/view-php.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view-php)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/view-php.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view-php)
[![License](https://img.shields.io/packagist/l/codemonster-ru/view-php.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view-php)
[![Tests](https://github.com/codemonster-ru/view-php/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/view-php/actions/workflows/tests.yml)

PHP template engine for the [`codemonster-ru/view`](https://github.com/codemonster-ru/view) core.  
Uses the **core Locator** for consistent file resolution (dot-notation, namespaces, multiple base paths).

## 📦 Installation

Via Composer:

```bash
composer require codemonster-ru/view-php
```

## 🚀 Usage

```php
use Codemonster\View\View;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Engines\PhpEngine;

$locator = new DefaultLocator([__DIR__ . '/resources/views']); // can be an array
$engine = new PhpEngine($locator, 'php'); // or ['phtml','php']
$view = new View(['php' => $engine], 'php');

echo $view->render('emails.welcome', ['user' => 'Vasya']);
```

## 🧪 Testing

You can run tests with the command:

```bash
composer test
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/view-php/blob/main/LICENSE)
