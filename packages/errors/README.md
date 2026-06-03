> [!IMPORTANT]
> This repository is read-only.
>
> Development happens in the Annabel monorepo:
> https://github.com/codemonster-ru/annabel
>
> Issues and pull requests should be opened there.

# codemonster-ru/errors

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/errors.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/errors)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/errors.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/errors)
[![License](https://img.shields.io/packagist/l/codemonster-ru/errors.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/errors)
[![Tests](https://github.com/codemonster-ru/errors/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/errors/actions/workflows/tests.yml)

Universal package for handling exceptions and HTTP errors.

## Installation

```bash
composer require codemonster-ru/errors
```

## Quick Start

It can be used as part of a framework or on its own in any PHP project.

### Example 1. Minimal use

```php
use Codemonster\Errors\Handlers\SmartExceptionHandler;

require __DIR__ . '/vendor/autoload.php';

$handler = new SmartExceptionHandler();

set_exception_handler(function (Throwable $e) use ($handler) {
    $response = $handler->handle($e);

    if (php_sapi_name() !== 'cli') {
        http_response_code($response->getStatusCode());

        echo (string) $response;
    } else {
        fwrite(STDERR, (string) $response . PHP_EOL);
    }
});

throw new RuntimeException('Something went wrong!');
```

When you run it, you'll get a neat HTML page (or a text fallback in the CLI),
with error information and the correct HTTP code.

### Example 2. Integration with a View renderer (e.g. from a framework)

```php
use Codemonster\Errors\Handlers\SmartExceptionHandler;
use Codemonster\View\View;

$view = new View(...);
$viewRenderer = fn(string $template, array $data) => $view->render($template, $data);
$handler = new SmartExceptionHandler($viewRenderer, debug: true);

try {
    throw new RuntimeException('Demo error');
} catch (Throwable $e) {
    $response = $handler->handle($e);

    echo $response;
}
```

## Template structure

```
resources/views/errors/
- generic.php # error page for production
- debug.php # debug page for developers
- 404.php # optional, per-status page
- 500.php # optional, per-status page
```

Any 3-digit HTTP status file will be used when present.
You can override the template base path with the third constructor argument.
Constructor: `new SmartExceptionHandler(?callable $viewRenderer = null, bool $debug = false, ?string $templatePath = null)`

Example:

```php
$handler = new SmartExceptionHandler(
    viewRenderer: null,
    debug: false,
    templatePath: __DIR__ . '/resources/views/errors'
);
```

## Behavior

-   Uses `errors.debug` when `debug: true`.
-   Uses `errors.<status>` when a status-specific template exists.
-   Falls back to `errors.generic`, then to a plain-text response.
-   In debug mode, renderer exceptions are rethrown.

## Testing

You can run tests with the command:

```bash
composer test
```

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/errors/blob/main/LICENSE)
