# codemonster-ru/razor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/razor.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/razor)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/razor.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/razor)
[![License](https://img.shields.io/packagist/l/codemonster-ru/razor.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/razor)
[![Tests](https://github.com/codemonster-ru/razor/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/razor/actions/workflows/tests.yml)

Template engine for PHP.

## 📦 Installation

Via Composer:

```bash
composer require codemonster-ru/razor
```

## 🚀 Usage

```php
use Codemonster\View\View;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\Razor\RazorEngine;

$locator = new DefaultLocator([__DIR__ . '/resources/views']); // you can specify an array of paths
$engine = new RazorEngine($locator, 'razor.php', __DIR__ . '/storage/cache/views');

$view = new View(['razor' => $engine], 'razor');

echo $view->render('emails.welcome', ['user' => 'Vasya']);
```

📄 **resources/views/emails/welcome.razor.php**

```html
<h1>Hello, {{ $user }}</h1>

@if($user === 'Vasya')
<p>Welcome back!</p>
@endif
```

## 🧪 Testing

You can run tests with the command:

```bash
composer test
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/razor/blob/main/LICENSE)
