<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale) ?>">

<head>
    <meta charset="UTF-8">
    <title>🧬 <?= htmlspecialchars($site) ?> — System Info</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #fafafa;
            color: #222;
            padding: 0;
        }

        code {
            background: #eee;
            padding: 2px 4px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <h1>🧬 <?= htmlspecialchars($site) ?> — System Info</h1>
    <ul>
        <li>PHP: <b><?= phpversion() ?></b></li>
        <li>Base path: <code><?= $base ?></code></li>
        <li>Time: <code><?= date('Y-m-d H:i:s') ?></code></li>
        <li>Locale: <code><?= $locale ?></code></li>
        <li>Timezone: <code><?= $timezone ?></code></li>
    </ul>
    <p>Config: <code>config/cms.php</code></p>
</body>

</html>