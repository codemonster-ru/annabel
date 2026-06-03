# codemonster-ru/dumper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/dumper.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/dumper)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/dumper.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/dumper)
[![License](https://img.shields.io/packagist/l/codemonster-ru/dumper.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/dumper)
[![Tests](https://github.com/codemonster-ru/dumper/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/dumper/actions/workflows/tests.yml)

A simple and elegant PHP variable dumper for both **CLI** and **web** environments.

## 📦 Installation

Via Composer:

```bash
composer require codemonster-ru/dumper
```

## 🚀 Usage

```php
use Codemonster\Dumper\Dumper;

// Automatically detects environment (CLI or HTML)
Dumper::dump($value);

// Force a specific mode
Dumper::dump($value, 'cli');
Dumper::dump($value, 'html');
```

### Dump and Die

If you want to dump a value and immediately stop script execution:

```php
use Codemonster\Dumper\Dumper;

// Dump a single value and exit
Dumper::dd($user);

// Dump multiple values
Dumper::dd($request, $response);

// Force HTML or CLI mode explicitly
Dumper::dd($data, 'html');
Dumper::dd($data, 'cli');
```

## 🧪 Testing

You can run tests with the command:

```bash
composer test
```

## 👨‍💻 Author

[Kirill Kolesnikov](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/dumper/blob/main/LICENSE)
