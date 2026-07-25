<?php /** @var \Codemonster\Cms\Modules\Settings\Models\SiteSetting $site */ ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) ($site->locale ?? 'en'), ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? 'Page not found') . ' | ' . (string) ($site->site_name ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
</head>

<body>
    <main>
        <h1>Page not found</h1>
    </main>
</body>

</html>
