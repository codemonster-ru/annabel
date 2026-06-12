---
title: "Views"
description: "Rendering PHP views and Vite assets"
order: 6
---

# Views

Annabel renders views from `resources/views`.

## Render a view

Render templates by name and pass their input as an associative array.

```php
return view('home', [
    'title' => 'Welcome to Annabel',
]);
```

## PHP template

Templates receive the provided data as variables and produce the response
markup.

```php
<!-- resources/views/home.php -->
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
```

## Vite assets

Use the Vite helper to include development or built frontend assets.

```php
<?= vite('resources/js/app.js') ?>
```

Run Vite locally:

```bash
npm run dev
```

Build assets for production:

```bash
npm run build
```
