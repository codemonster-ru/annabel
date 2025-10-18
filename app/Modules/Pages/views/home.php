<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Xen CMS') ?></title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #fafafa;
            color: #333;
        }

        h1 {
            color: #4e7;
        }
    </style>
</head>

<body>
    <h1>🧘 Welcome to <?= htmlspecialchars($title ?? 'Xen CMS') ?></h1>
    <p>View loaded from: <code>app/Modules/Pages/Views/home.php</code></p>
</body>

</html>