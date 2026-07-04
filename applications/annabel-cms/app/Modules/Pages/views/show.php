<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) config('cms.locale', 'en'), ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? config('cms.name', 'Annabel CMS')), ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body {
            margin: 0;
            background: #f7f7f5;
            color: #1f2933;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.6;
        }

        main {
            inline-size: min(720px, calc(100% - 2rem));
            margin: 0 auto;
            padding: 4rem 0;
        }

        h1 {
            margin: 0 0 1rem;
            font-size: clamp(2rem, 5vw, 3.5rem);
            line-height: 1.1;
        }

        article {
            font-size: 1.0625rem;
        }
    </style>
</head>

<body>
    <main>
        <article>
            <h1><?= htmlspecialchars((string) ($title ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= nl2br(htmlspecialchars((string) ($content ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
        </article>
    </main>
</body>

</html>
