<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }

        input {
            display: block;
            margin-bottom: 1rem;
            width: 100%;
            padding: 8px;
        }

        button {
            padding: 8px 16px;
        }
    </style>
</head>

<body>
    <form method="post" action="/login">
        <?= csrf_field() ?>
        <h2>Login to Annabel CMS</h2>

        <?php if (!empty($error)): ?>
            <p style="color: red"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <label>Email:</label><br>
        <input type="email" name="email" autocomplete="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" autocomplete="current-password" required><br><br>

        <button type="submit">Login</button>
    </form>
</body>

</html>
