<?php /** @var string|null $user */ ?>
<p>Welcome, <?= htmlspecialchars($user ?? 'User', ENT_QUOTES, 'UTF-8') ?></p>
