# codemonster-ru/env

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/env.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/env)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/env.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/env)
[![License](https://img.shields.io/packagist/l/codemonster-ru/env.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/env)
[![Tests](https://github.com/codemonster-ru/env/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/env/actions/workflows/tests.yml)

A simple and lightweight `.env` loader for PHP projects.

## Installation

Via Composer:

```bash
composer require codemonster-ru/env
```

## Usage

Create a `.env` file in the root of your project:

```dotenv
APP_NAME=MyApp
FEATURE_ENABLED=true
FEATURE_DISABLED=false
OPTIONAL_VALUE=null
EMPTY_VALUE=empty
SSR_URL="http://localhost:3000"
WITH_SPACES="spaced value"
INLINE_COMMENT=works # comment
ESCAPED_HASH="foo#bar"
MULTILINE="line1\nline2"
export EXPORTED_VALUE=exported
```

Load .env in your app:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Codemonster\Env\Env;
use Codemonster\Env\EnvLoader;

Env::load(__DIR__ . '/.env');

echo Env::get('APP_NAME'); // "MyApp"
```

Optional casting:

```php
Env::getCast('FEATURE_ENABLED', false, true); // true
Env::getCast('OPTIONAL_VALUE', 'default', true); // null
```

Common options:

```php
// Encoding conversion (mbstring required)
Env::load(__DIR__ . '/.env', 'UTF-8');

// Size limit in bytes (negative size is invalid)
Env::load(__DIR__ . '/.env', null, 1048576);
Env::loadString("KEY=value\n", null, false, 1024);

// Strict resolve (throws if ${VAR} or $VAR is missing)
Env::load(__DIR__ . '/.env', null, null, true);

// Optional file (returns false if missing/unreadable/too large)
Env::safeLoad(__DIR__ . '/.env');
```

Parsing helpers:

```php
Env::parse("KEY=value\n");
Env::parse("KEY=value\n", null, true); // strict, rejects duplicates

Env::parseToArray("KEY=value\n");
Env::parseToArray("KEY=value\n", null, true); // strict, rejects duplicates
```

Multiple files and glob:

```php
Env::loadFiles([__DIR__ . '/.env', __DIR__ . '/.env.local']);
Env::loadFiles([__DIR__ . '/*.env'], null, null, false, true, GLOB_NOSORT);
```

ASCII-only names:

```php
Env::setDefaultParser(new DefaultParser(true));
Env::loadString("VALID_NAME=ok\n");
```

Isolated loader instance:

```php
$loader = new EnvLoader();
$loader->loadFile(__DIR__ . '/.env');
$loader->resetLoaded();
$loader->safeLoadFile(__DIR__ . '/.env');
$loader->loadFile(__DIR__ . '/.env', null, 1048576, true);
$loader->loadString("KEY=value\n");
$loader->loadString("KEY=${VAR}\n", null, true);
$loader->loadFiles([__DIR__ . '/.env', __DIR__ . '/.env.local']);
```

Notes:

-   When encoding is null, no conversion is performed, even if mbstring is available.
-   UTF-8 BOM is stripped automatically.

## Features

-   Loading `.env` files into `$_ENV`, `$_SERVER`, and via `putenv()`.
-   Does not override variables already present in `$_ENV`, `$_SERVER`, or `getenv()`.
-   Values are returned as strings by default; `Env::getCast()` enables optional casting.
-   Quoted strings with escaped quotes and `\n`, `\r`, `\t`, `\v`, `\f` in double quotes (single quotes are literal).
-   Inline comments start with `#` in unquoted values (use quotes to keep a literal `#`).
-   Unquoted values cannot contain whitespace.
-   `export KEY=value` support.
-   Unknown escape sequences in double quotes throw `InvalidFileException`.
-   Strict parsing: invalid lines, invalid variable names, and unclosed quotes throw `InvalidFileException`.
-   `${VAR}` and `$VAR` expansion are supported in quoted and unquoted values.
-   `${VAR}` and `$VAR` expand in double quotes and unquoted values; single quotes keep literals.
-   Lines without `=` are treated as empty strings.
-   Re-loading updates variables that were previously loaded by this library.
-   Optional encoding conversion (requires `mbstring`).
-   Optional strict resolve mode throws on unresolved `${VAR}` or `$VAR` references.
-   Default name rules allow Unicode letters/numbers; ASCII-only names are optional (via `DefaultParser(true)`).

## Parse Formats

| Method                        | Return format                               |
| ----------------------------- | ------------------------------------------- |
| `Env::parse()`                | Raw entries: `[[name, value, vars], ...]`   |
| `Env::parseToArray()`         | Associative map: `['NAME' => 'value', ...]` |
| `EnvParser::parseStringRaw()` | Raw entries: `[[name, value, vars], ...]`   |

Strict mode throws `InvalidFileException` on duplicate names for `parse()` and `parseToArray()`.

## Load Files Order

-   `loadFiles()` processes paths in the order provided.
-   Glob patterns are expanded using `glob()` with `GLOB_BRACE` by default.
-   Glob matches are sorted to keep load order stable.
-   Use the optional `globFlags` parameter to change `glob()` behavior.
-   If `strictResolve` is true, unresolved `${VAR}` or `$VAR` references in any loaded file will throw `InvalidFileException`.
-   If `shortCircuit` is true, the first successfully loaded file stops further processing.
-   If `shortCircuit` is false, missing files raise `InvalidPathException`.

## Exceptions

| Scenario                                  | Exception                                            |
| ----------------------------------------- | ---------------------------------------------------- |
| Invalid syntax, invalid name, bad escapes | `Codemonster\Env\Exception\InvalidFileException`     |
| Invalid or unsupported encoding           | `Codemonster\Env\Exception\InvalidEncodingException` |
| Missing or unreadable file                | `Codemonster\Env\Exception\InvalidPathException`     |

## Loader Interface

You can provide your own loader by implementing `Codemonster\Env\LoaderInterface`:

```php
<?php

use Codemonster\Env\Env;
use Codemonster\Env\LoaderInterface;

class MyLoader implements LoaderInterface
{
    public function resetLoaded(): void
    {
        // reset internal state
    }

    public function loadFile(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false
    ): void
    {
        // custom loading logic
    }

    public function safeLoadFile(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false
    ): bool
    {
        // return false if missing/unreadable
        return false;
    }
}

Env::setDefaultLoader(new MyLoader());
```

## Parser Interface

You can provide a custom parser to `EnvLoader` by implementing `Codemonster\Env\ParserInterface`:

```php
<?php

use Codemonster\Env\DefaultParser;
use Codemonster\Env\EnvLoader;
use Codemonster\Env\ParserInterface;

class MyParser implements ParserInterface
{
    public function parseStringRaw(string $content, ?string $encoding = null): array
    {
        return (new DefaultParser())->parseStringRaw($content, $encoding);
    }

    public function expandVariables(string $value, array $vars): string
    {
        return (new DefaultParser())->expandVariables($value, $vars);
    }

    public function expandVariablesWithReport(string $value, array $vars): array
    {
        return (new DefaultParser())->expandVariablesWithReport($value, $vars);
    }
}

$loader = new EnvLoader(new MyParser());
```

`DefaultParser` accepts an optional boolean for ASCII-only variable names:

```php
$parser = new DefaultParser(true); // match phpdotenv name rules
```

## Testing

You can run tests with the command:

```bash
composer test
```

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/env/blob/main/LICENSE)
