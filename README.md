# Xen CMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/xen.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/xen)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/xen.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/xen)
[![License](https://img.shields.io/packagist/l/codemonster-ru/xen.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/xen)

**Xen** is a modern modular CMS based on the [Annabel](https://github.com/codemonster-ru/annabel) framework,
designed for clean architecture, simple code, and extensibility through independent modules.

## 📦 Installation

```bash
composer require codemonster-ru/xen
```

## ✨ Features

-   📦 **Modular structure**
    Each CMS component is a separate module (`Pages`, `Users`, `Admin`, etc.).

-   ⚙️ **Automatic module loading**
    `ModuleManager` finds and activates all `ModuleServiceProvider` in `app/Modules`.

-   🧘 **Minimal bootstrap**
    `bootstrap/app.php` only contains the creation of the `Application` instance.

-   🎨 **Templates within modules**
    Each module can have its own templates (`Views/`)
    and access them via `view('pages::home')`.

-   🔌 **Annabel Compatibility**
    Uses all core features: service providers, container, view engine, Router, etc.

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/xen/blob/main/LICENSE)
