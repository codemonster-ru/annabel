<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Exception — <?= htmlspecialchars($exception->getMessage()) ?></title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #f6f8fa;
            color: #24292f;
            margin: 0;
        }

        header {
            background: #d73a49;
            color: white;
            padding: 1rem 2rem;
        }

        h1 {
            font-size: 1.5rem;
            margin: 0;
        }

        h2 {
            margin-top: 0;
        }

        main {
            padding: 2rem;
        }

        pre {
            background: #fff;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #e1e4e8;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        td,
        th {
            padding: .5rem .75rem;
            border-bottom: 1px solid #e1e4e8;
            font-family: monospace;
            font-size: .9rem;
        }
    </style>
</head>

<body>
    <header>
        <h1><?= htmlspecialchars($exception->getMessage()) ?></h1>
        <div>in <strong><?= htmlspecialchars($exception->getFile()) ?></strong> line <strong><?= $exception->getLine() ?></strong></div>
    </header>
    <main>
        <h2>Stack trace</h2>
        <pre><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
    </main>
</body>

</html>