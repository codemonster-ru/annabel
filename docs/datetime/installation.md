---
title: "Installation"
description: "Install codemonster-ru/datetime"
order: 2
---

# Installation

Install the package with Composer:

```bash
composer require codemonster-ru/datetime:^1.0
```

Version `1.0` requires PHP 8.2 or newer and installs `psr/clock` automatically.

`LocalizedFormatter` and `HumanDiffFormatter` additionally require `ext-intl`
and the corresponding ICU locale data to be installed by the operating system.
The Annabel Docker image includes the extension and the full ICU data set.

The package does not require Annabel and can be used in any PHP application.
