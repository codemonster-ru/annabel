---
title: "Getting started"
description: "Database connections and basic queries"
order: 1
---

# Getting started

Annabel registers database services when database configuration is present.

## Configuration

Configure connections in `config/database.php` and environment values in `.env`.

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=annabel
DB_USERNAME=annabel
DB_PASSWORD=secret
```

SQLite is also supported:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

The database package requires `ext-pdo` and the PDO driver for the selected
connection.

## Query

```php
$users = db()
    ->table('users')
    ->where('active', 1)
    ->get();
```

## Transactions

```php
transaction(function () {
    db()->table('logs')->insert(['type' => 'created']);
});
```

## Schema and migrations

Create schema changes with migrations:

```bash
php vendor/bin/annabel make:migration create_posts_table
php vendor/bin/annabel migrate
```

Migration paths and the migration table are configured in `config/database.php`.
