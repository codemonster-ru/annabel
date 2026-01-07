<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile</title>
</head>

<body>
    <h2>Profile</h2>

    <?php if (is_array($user ?? null)): ?>
        <ul>
            <li><strong>ID:</strong> <?= htmlspecialchars((string)($user['id'] ?? '')) ?></li>
            <li><strong>Email:</strong> <?= htmlspecialchars((string)($user['email'] ?? '')) ?></li>
            <?php
            $roles = $user['roles'] ?? null;
            $rolesText = is_array($roles) ? implode(', ', $roles) : (string)($user['role'] ?? '');
            ?>
            <li><strong>Roles:</strong> <?= htmlspecialchars($rolesText) ?></li>
        </ul>
    <?php endif; ?>

    <?php
    $token = session()->get('_csrf_token');

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        session()->put('_csrf_token', $token);
    }
    ?>

    <form method="post" action="/logout">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token) ?>">
        <button type="submit">Logout</button>
    </form>
</body>

</html>
