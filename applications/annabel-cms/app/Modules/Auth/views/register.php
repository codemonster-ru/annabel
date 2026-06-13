<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>

<body>

    <?php
    $token = session()->get('_csrf_token');

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        session()->put('_csrf_token', $token);
    }
    ?>

    <form method="post" action="/register">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token) ?>">
        <h2>Register</h2>

        <?php if (!empty($error)): ?>
            <p style="color: red"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <label>Name:</label>
        <input type="text" name="name" autocomplete="name" required>

        <label>Email:</label>
        <input type="email" name="email" autocomplete="email" required>

        <label>Password:</label>
        <input type="password" name="password" autocomplete="new-password" required>

        <label>Confirm password:</label>
        <input type="password" name="password_confirmation" autocomplete="new-password" required>

        <button type="submit">Sign up</button>
    </form>

</body>

</html>