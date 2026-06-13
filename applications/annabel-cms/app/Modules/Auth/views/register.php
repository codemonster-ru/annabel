<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>

<body>

    <form method="post" action="/register">
        <?= csrf_field() ?>
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
