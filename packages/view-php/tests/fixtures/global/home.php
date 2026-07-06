<?php /** @var string|null $name */ ?>
<h1>Hello, <?= htmlspecialchars($name ?? 'World', ENT_QUOTES, 'UTF-8') ?>!</h1>
