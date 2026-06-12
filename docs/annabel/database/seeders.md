---
title: "Seeders"
description: "Seeding database records"
order: 5
---

# Seeders

Seeders populate the database with initial or test data.

## Create a seeder

Generate a seeder class for repeatable development or reference data.

```bash
php vendor/bin/annabel make:seed UserSeeder
```

## Run seeders

Run all registered seeders after the target database schema is available.

```bash
php vendor/bin/annabel seed
```

Keep seeders deterministic so they are safe to run in repeatable environments.
