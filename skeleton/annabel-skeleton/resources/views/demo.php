<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Annabel Framework">
    <title>Annabel Demo</title>
    <?= vite('resources/js/app.js') ?>
</head>

<body>
    <header>
        <h1>Annabel Demo</h1>
        <nav>
            <a href="/">Home</a>
            <a href="/demo">Demo</a>
            <a href="/demo/json">JSON</a>
        </nav>
    </header>

    <main class="demo-shell">
        <section class="demo-intro">
            <h2>Local monorepo playground</h2>
            <p>This page exercises the skeleton, framework, router, HTTP layer, view renderer, security middleware, validation, cache, session, database, filesystem, events, queue, mail, logging, auth, config, scheduler, HTTP client, errors, and API resource packages from local symlinks.</p>
        </section>

        <?php if ($notice): ?>
            <p class="notice"><?= htmlspecialchars((string) $notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="demo-grid">
            <article>
                <h3>Cache</h3>
                <p>Writes a timestamp through the PSR simple-cache binding and reads it back.</p>
                <?php if ($cacheValue): ?>
                    <code><?= htmlspecialchars((string) $cacheValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/cache">
                    <?= csrf_field() ?>
                    <button type="submit">Run cache check</button>
                </form>
            </article>

            <article>
                <h3>Filesystem</h3>
                <p>Writes a file to the configured local disk and reads it back.</p>
                <?php if (isset($results['filesystem'])): ?>
                    <code><?= htmlspecialchars(json_encode($results['filesystem'], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/filesystem">
                    <?= csrf_field() ?>
                    <button type="submit">Write file</button>
                </form>
            </article>

            <article>
                <h3>Events</h3>
                <p>Registers a listener and dispatches an event object through the dispatcher.</p>
                <?php if (isset($results['events'])): ?>
                    <code><?= htmlspecialchars(json_encode($results['events'], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/events">
                    <?= csrf_field() ?>
                    <button type="submit">Dispatch event</button>
                </form>
            </article>

            <article>
                <h3>Session</h3>
                <p>Increments a counter stored in the configured session driver.</p>
                <?php if ($sessionCount !== null): ?>
                    <code>count: <?= (int) $sessionCount ?><?= $sessionId ? ' / cookie: ' . htmlspecialchars((string) $sessionId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '' ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/session">
                    <?= csrf_field() ?>
                    <button type="submit">Touch session</button>
                </form>
            </article>

            <article>
                <h3>Queue</h3>
                <p>Pushes a small job through the configured queue connection.</p>
                <?php if (isset($results['queue'])): ?>
                    <code><?= htmlspecialchars(json_encode($results['queue'], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/queue">
                    <?= csrf_field() ?>
                    <button type="submit">Push job</button>
                </form>
            </article>

            <article>
                <h3>HTTP client</h3>
                <p>Builds a request and sends it through a local test transport.</p>
                <?php if (isset($results['http_client'])): ?>
                    <code><?= htmlspecialchars(json_encode($results['http_client'], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/http-client">
                    <?= csrf_field() ?>
                    <button type="submit">Send request</button>
                </form>
            </article>

            <article>
                <h3>Validation</h3>
                <p>Submits a small form through CSRF middleware and validates the payload.</p>
                <form method="post" action="/demo/validation" class="stacked">
                    <?= csrf_field() ?>
                    <label>
                        Name
                        <input name="name" value="<?= htmlspecialchars((string) ($formInput['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </label>
                    <label>
                        Email
                        <input name="email" value="<?= htmlspecialchars((string) ($formInput['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="hello@example.com">
                    </label>
                    <button type="submit">Validate form</button>
                </form>
                <?php if ($validationErrors): ?>
                    <ul class="errors">
                        <?php foreach ($validationErrors as $messages): ?>
                            <?php foreach ($messages as $message): ?>
                                <li><?= htmlspecialchars((string) $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php elseif ($validated): ?>
                    <code><?= htmlspecialchars(json_encode($validated, JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
            </article>

            <article>
                <h3>Database</h3>
                <p>Creates a tiny demo table if needed, inserts a row, and counts records.</p>
                <?php if ($databaseTotal !== null): ?>
                    <code>demo_events rows: <?= (int) $databaseTotal ?></code>
                <?php endif; ?>
                <?php if ($databaseError): ?>
                    <code><?= htmlspecialchars((string) $databaseError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/database">
                    <?= csrf_field() ?>
                    <button type="submit">Write database row</button>
                </form>
            </article>

            <article>
                <h3>Errors</h3>
                <p>Renders a handled 404 response without crashing the page.</p>
                <?php if (isset($results['errors'])): ?>
                    <code><?= htmlspecialchars(json_encode($results['errors'], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/errors">
                    <?= csrf_field() ?>
                    <button type="submit">Render error</button>
                </form>
            </article>

            <article>
                <h3>Mail and logging</h3>
                <p>Sends a message through the configured mailer and writes a log entry.</p>
                <?php if (isset($results['mail'])): ?>
                    <code><?= htmlspecialchars(json_encode($results['mail'], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/mail">
                    <?= csrf_field() ?>
                    <button type="submit">Send demo mail</button>
                </form>
            </article>

            <article>
                <h3>Auth</h3>
                <p>Creates and verifies a password hash through the auth package.</p>
                <?php if (isset($results['auth'])): ?>
                    <code><?= htmlspecialchars(json_encode($results['auth'], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/auth">
                    <?= csrf_field() ?>
                    <button type="submit">Verify hasher</button>
                </form>
            </article>

            <article>
                <h3>Config and scheduler</h3>
                <p>Reads config/env values and registers a scheduled task.</p>
                <?php if (isset($results['config'], $results['scheduler'])): ?>
                    <code><?= htmlspecialchars(json_encode([
                        'config' => $results['config'],
                        'scheduler' => $results['scheduler'],
                    ], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
                <?php endif; ?>
                <form method="post" action="/demo/config">
                    <?= csrf_field() ?>
                    <button type="submit">Read services</button>
                </form>
            </article>
        </section>
    </main>

    <footer>
        &copy; <?= date('Y') ?> <a href="https://github.com/KolesnikovKirill" target="_blank" rel="noopener noreferrer">Kirill Kolesnikov</a>
    </footer>
</body>

</html>
