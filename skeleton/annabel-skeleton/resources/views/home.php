<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Annabel Framework">
    <title>Welcome to Annabel</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            margin: 0;
            padding: 0;
            background: #f7fafc;
            color: #2d3748;
        }

        header {
            background: #4c51bf;
            color: white;
            padding: 1rem;
            text-align: center;
        }

        main {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        h1 {
            margin-top: 0;
        }

        footer {
            text-align: center;
            padding: 1.5rem 0;
            font-size: 0.9rem;
            color: #718096;
        }

        a {
            color: #4c51bf;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <header>
        <h1>Annabel Framework</h1>
    </header>
    <main>
        <h2>Hello, <?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
        <p>You are running your first page with <strong>Annabel</strong>.</p>
        <p>Edit this file at <code>resources/views/home.php</code> to get started.</p>
    </main>
    <footer>
        &copy; <?= date('Y') ?> Annabel Framework<br>
        Created by <a href="https://github.com/codemonster-ru" target="_blank" rel="noopener noreferrer">Codemonster</a>
    </footer>
</body>

</html>
