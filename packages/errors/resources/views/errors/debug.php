<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($exception->getMessage()) ?></title>
    <style>
        :root {
            color-scheme: light dark;
        }

        body {
            background: #f9fafb;
            color: #1e293b;
            font-family: "Segoe UI", Roboto, system-ui, sans-serif;
            margin: 0;
            padding: 60px 20px;
            display: flex;
            justify-content: center;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 880px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            padding: 32px 40px;
            box-sizing: border-box;
        }

        .header {
            margin-bottom: 28px;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 600;
            color: #007bff;
            margin: 0;
            line-height: 1.3;
        }

        .header small {
            color: #64748b;
            font-size: 14px;
            margin-left: 6px;
        }

        .meta {
            font-family: monospace;
            font-size: 14px;
            background: #f3f4f6;
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 18px;
            color: #1e293b;
        }

        .trace {
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            margin-top: 12px;
        }

        .trace h2 {
            font-size: 15px;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        pre {
            background: #f8fafc;
            border-radius: 8px;
            padding: 14px;
            font-size: 13px;
            line-height: 1.55;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }

        footer {
            margin-top: 24px;
            font-size: 13px;
            color: #94a3b8;
            text-align: right;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #0a192f;
                color: #e2e8f0;
            }

            .wrapper {
                background: #1e293b;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6);
            }

            .meta {
                background: #0f172a;
                color: #e2e8f0;
            }

            .trace {
                border-top-color: #334155;
            }

            .trace h2 {
                color: #cbd5e1;
            }

            pre {
                background: #0f172a;
                border-color: #334155;
                color: #e2e8f0;
            }

            footer {
                color: #64748b;
            }
        }
    </style>
</head>

<body>

    <div class="wrapper">
        <div class="header">
            <?php
            $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
            ?>
            <h1><?= htmlspecialchars($exception->getMessage()) ?> <small>(HTTP <?= htmlspecialchars($status) ?>)</small></h1>
        </div>

        <div class="meta">
            <?= htmlspecialchars($exception->getFile()) ?> : <?= htmlspecialchars($exception->getLine()) ?>
        </div>

        <div class="trace">
            <h2>Stack trace</h2>
            <pre><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
        </div>

        <footer>Codemonster Errors — Debug Mode</footer>
    </div>

</body>

</html>