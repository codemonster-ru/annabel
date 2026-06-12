---
title: "Query builder"
description: "Building and executing database queries"
order: 2
---

# Query builder

The query builder provides a fluent interface for SQL operations.

## Select

Build a select query from a table and retrieve its matching rows.

```php
$users = db()->table('users')
    ->select('id', 'email')
    ->where('active', 1)
    ->orderBy('created_at', 'desc')
    ->get();
```

Fetch a single row:

```php
$user = db()->table('users')
    ->where('id', 1)
    ->first();
```

## Where clauses

Add constraints to limit which rows a query reads or modifies.

```php
db()->table('users')
    ->where('active', 1)
    ->orWhere('role', 'admin')
    ->whereIn('id', [1, 2, 3])
    ->whereNull('deleted_at')
    ->get();
```

Nested groups are supported with a callback:

```php
db()->table('posts')
    ->where(function ($query) {
        $query->where('published', 1)
            ->orWhere('featured', 1);
    })
    ->get();
```

## Joins and grouping

Combine related tables and aggregate rows when a query spans multiple records
or sources.

```php
db()->table('posts')
    ->join('users', 'users.id', '=', 'posts.user_id')
    ->groupBy('users.id')
    ->having('posts_count', '>', 0)
    ->get();
```

Use `leftJoin()`, `rightJoin()`, and `crossJoin()` when needed.

## Insert

Insert one or more rows using associative column data.

```php
db()->table('users')->insert([
    'email' => 'hello@example.com',
]);
```

## Update

Apply new values to every row matched by the query constraints.

```php
db()->table('users')
    ->where('id', 1)
    ->update(['active' => 1]);
```

## Delete

Delete every row matched by the query constraints.

```php
db()->table('users')
    ->where('id', 1)
    ->delete();
```

## Aggregates

Calculate counts and scalar aggregates without loading full result rows.

```php
$count = db()->table('users')->count();
$total = db()->table('orders')->sum('amount');
$average = db()->table('orders')->avg('amount');
```

Available helpers include `count()`, `sum()`, `avg()`, `min()`, `max()`,
`exists()`, `doesntExist()`, `value()`, and `pluck()`.

## Pagination

Paginate a query when results should be returned in bounded pages.

```php
$page = db()->table('users')
    ->orderBy('id')
    ->simplePaginate(perPage: 20, page: 1);
```

`simplePaginate()` returns data and simple previous/next pagination metadata.

## Debugging SQL

Inspect the generated SQL and bindings before executing a query.

```php
$query = db()->table('users')->where('active', 1);

$sql = $query->toSql();
$bindings = $query->getBindings();
```
