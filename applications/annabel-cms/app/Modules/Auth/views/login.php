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
    <?php
    $token = session()->get('_csrf_token');

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        session()->put('_csrf_token', $token);
    }
    ?>

    <form method="post" action="/login">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token) ?>">
        <h2>Login to Xen</h2>

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