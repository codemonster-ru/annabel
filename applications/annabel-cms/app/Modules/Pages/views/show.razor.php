<!DOCTYPE html>
<html lang="{{ $site->locale ?? 'en' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($meta_title ?? $title ?? '') . ' | ' . ($site->site_name ?? '') }}</title>
    @if (trim((string) ($meta_description ?? '')) !== '')
        <meta name="description" content="{{ $meta_description }}">
    @endif
    <link rel="stylesheet" href="/vendor/codemonster-ui/css/tokens/tokens.css">
    <link rel="stylesheet" href="/vendor/codemonster-ui/css/css/styles.css">
    <style>
        body {
            margin: 0;
            background: var(--cm-color-background-canvas);
            color: var(--cm-color-text-primary);
            font-family: var(--cm-font-family-base);
        }

        .cms-page {
            padding-block: var(--cm-space-12);
        }

        .cms-page__content {
            white-space: pre-line;
        }
    </style>
</head>

<body>
    <cm-container element="main" size="md" class="cms-page">
        <cm-stack>
            <cm-card element="article" :title="$title">
                <p class="cms-page__content">{{ $content }}</p>
            </cm-card>
        </cm-stack>
    </cm-container>
</body>

</html>
