---
title: "Getting started"
description: "First standalone usage of codemonster-ru/filesystem"
order: 1
---

# Getting started

`codemonster-ru/filesystem` provides local filesystem disks and a disk manager.

## Basic usage

Create a filesystem instance for a root directory, then perform operations
relative to that root.

```php
use Codemonster\Filesystem\LocalFilesystem;

$disk = new LocalFilesystem(__DIR__ . '/storage/app');

$disk->put('reports/today.txt', 'Ready');

$contents = $disk->get('reports/today.txt');
```

Use `FilesystemManager` when you need named disks from configuration.
