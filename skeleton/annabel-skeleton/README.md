# Annabel Skeleton

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/annabel-skeleton.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-skeleton)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/annabel-skeleton.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-skeleton)
[![License](https://img.shields.io/packagist/l/codemonster-ru/annabel-skeleton.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-skeleton)

Starter project for the **[Annabel PHP Framework](https://github.com/codemonster-ru/annabel)**.
A clean and modern foundation for building full-stack web applications.

## Quick Start

Create a new project using Composer:

```bash
composer create-project codemonster-ru/annabel-skeleton myapp
cd myapp
composer start
```

Open [http://localhost:8000](http://localhost:8000) to see your first page rendered by **Annabel**.

## Security Defaults

The `codemonster-ru/security` package is enabled by default.

-   CSRF and throttling are auto-registered in the kernel.
-   Use `.env` to disable or tune `SECURITY_*` settings.

Default `SECURITY_*` values:

```dotenv
SECURITY_CSRF_ENABLED=true
SECURITY_CSRF_ADD_TO_KERNEL=true
SECURITY_CSRF_VERIFY_JSON=false
SECURITY_CSRF_INPUT_KEY=_token
SECURITY_THROTTLE_ENABLED=true
SECURITY_THROTTLE_ADD_TO_KERNEL=true
SECURITY_THROTTLE_MAX_ATTEMPTS=60
SECURITY_THROTTLE_DECAY_SECONDS=60
```

## Requirements

-   PHP **8.2** or higher
-   Composer

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/annabel-skeleton/blob/main/LICENSE)
