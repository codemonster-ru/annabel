<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Annabel Framework">
    <title>Welcome to Annabel</title>
    <?= vite('resources/js/app.js') ?>
</head>

<body>
    <header>
        <h1>Annabel Framework</h1>
    </header>
    <main>
        <h2>Hello, <?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
        <p>You are running your first page with <strong>Annabel</strong>.</p>
        <p>Edit this file at <code>resources/views/home.php</code> to get started.</p>
        <p><a class="button-link" href="/demo">Open framework demo</a></p>
    </main>
    <footer>
        &copy; <?= date('Y') ?> <a href="https://github.com/KolesnikovKirill" target="_blank" rel="noopener noreferrer">Kirill Kolesnikov</a>
    </footer>
</body>

</html>
