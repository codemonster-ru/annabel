# codemonster-ru/view

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/view.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/view.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view)
[![License](https://img.shields.io/packagist/l/codemonster-ru/view.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/view)
[![Tests](https://github.com/codemonster-ru/view/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/view/actions/workflows/tests.yml)

A core for rendering views in PHP applications.

The package itself doesn't contain any engines; they are included in separate packages:

-   [`codemonster-ru/view-php`](https://github.com/codemonster-ru/view-php) - PHP templates
-   [`codemonster-ru/view-ssr`](https://github.com/codemonster-ru/view-ssr) - SSR for Vue/React
-   (future) Twig, Blade, and others

## Installation

```bash
composer require codemonster-ru/view
```

## Usage

```php
use Codemonster\View\View;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Engines\PhpEngine; // package: codemonster-ru/view-php

$locator = new DefaultLocator([__DIR__ . '/resources/views']); // can be an array of paths
$engine  = new PhpEngine($locator, 'php'); // default extension: php

$view = new View(['php' => $engine], 'php');

// Render template
echo $view->render('emails.welcome', ['user' => 'Vasya']);
// Looks for: resources/views/emails/welcome.php

// Access the locator of the default engine (added in v2.2.0)
$defaultLocator = $view->getLocator();
$defaultLocator->addPath(__DIR__ . '/vendor/package/views');

// Register namespaced view paths (added in v2.3.0)
$view->addNamespace('admin', __DIR__ . '/resources/views/admin');
echo $view->render('admin::dashboard', ['user' => 'Vasya']);
```

## Features

-   Engine-agnostic core
-   Support for multiple engines (`PhpEngine`, `SsrEngine`, `TwigEngine`, etc.)
-   Unified `EngineInterface` interface
-   Easy integration with frameworks (e.g., Annabel)

## Testing

You can run tests with the command:

```bash
composer test
```

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/view/blob/main/LICENSE)
