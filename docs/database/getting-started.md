---
title: "Getting started"
description: "First standalone usage of codemonster-ru/database"
order: 1
---

# Getting started

`codemonster-ru/database` is a framework-agnostic database layer with PDO
connections, a fluent query builder, schema builder, migrations, seeders, and
active-record style models.

## Basic usage

Create a database connection and execute queries through the connection API.

```php
use Codemonster\Database\DatabaseManager;

$database = new DatabaseManager([
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/database.sqlite',
        ],
    ],
]);

$users = $database->connection()
    ->table('users')
    ->where('active', 1)
    ->get();
```
