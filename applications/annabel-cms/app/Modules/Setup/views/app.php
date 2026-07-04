<?php
$title = 'Install Annabel CMS';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <?php if (!empty($assets['favicon']) && is_string($assets['favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($assets['favicon'], ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml">
    <?php endif; ?>
    <?php foreach ($assets['styles'] ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>

<body>
    <div id="setup-app" v-cloak></div>
    <script>
        window.__ANNABEL_CMS_SETUP__ = <?= json_encode($boot ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script type="module" src="<?= htmlspecialchars($assets['script'], ENT_QUOTES, 'UTF-8') ?>"></script>
</body>

</html>
