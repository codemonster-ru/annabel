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
            <li><strong>ID:</strong> <?= htmlspecialchars((string) ($user['id'] ?? '')) ?></li>
            <li><strong>Email:</strong> <?= htmlspecialchars((string) ($user['email'] ?? '')) ?></li>
            <?php
            $roles = $user['roles'] ?? null;
        $rolesText = is_array($roles) ? implode(', ', $roles) : (string) ($user['role'] ?? '');
        ?>
            <li><strong>Roles:</strong> <?= htmlspecialchars($rolesText) ?></li>
        </ul>
    <?php endif; ?>

    <form method="post" action="/logout">
        <?= csrf_field() ?>
        <button type="submit">Logout</button>
    </form>
</body>

</html>
