---
title: "Seeders"
description: "Seeding database records"
order: 5
---

# Seeders

Seeders populate the database with initial or test data.

## Create a seeder

```bash
php vendor/bin/annabel make:seed UserSeeder
```

## Run seeders

```bash
php vendor/bin/annabel seed
```

Keep seeders deterministic so they are safe to run in repeatable environments.
