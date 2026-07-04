<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) config('cms.locale', 'en'), ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? 'Page not found'), ENT_QUOTES, 'UTF-8') ?></title>
</head>

<body>
    <main>
        <h1>Page not found</h1>
    </main>
</body>

</html>
