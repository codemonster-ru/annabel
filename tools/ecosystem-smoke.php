<?php

declare(strict_types=1);

use App\Providers\SecurityServiceProvider as ApplicationSecurityServiceProvider;
use Codemonster\Annabel\Application;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Events\EventDispatcher;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Security\Csrf\CsrfTokenManager;
use Codemonster\Validation\Validator;
use Psr\SimpleCache\CacheInterface;

$root = dirname(__DIR__);
$defaultSkeleton = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/annabel-skeleton-acceptance';
$skeleton = is_dir($defaultSkeleton)
    ? $defaultSkeleton
    : $root . '/skeleton/annabel-skeleton';
$autoload = $skeleton . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(
        STDERR,
        "Skeleton dependencies are missing. Run `composer install:ecosystem` first.\n",
    );

    exit(1);
}

require $autoload;

/**
 * @param mixed $actual
 * @param mixed $expected
 */
function assertSameValue(mixed $actual, mixed $expected, string $message): void
{
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

putenv('APP_ENV=testing');
putenv('APP_DEBUG=false');
putenv('CACHE_STORE=array');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('LOG_CHANNEL=null');
putenv('SESSION_DRIVER=array');
putenv('SECURITY_CSRF_ENABLED=true');
putenv('SECURITY_CSRF_ADD_TO_KERNEL=true');
putenv('SECURITY_THROTTLE_ENABLED=true');
putenv('SECURITY_THROTTLE_ADD_TO_KERNEL=true');

Application::resetInstance();

try {
    /** @var Application $app */
    $app = require $skeleton . '/bootstrap/app.php';

    assertCondition(
        in_array(
            ApplicationSecurityServiceProvider::class,
            array_map(static fn (object $provider): string => $provider::class, $app->getProviders()),
            true,
        ),
        'The skeleton security provider was not discovered.',
    );

    $home = $app->handle(new Request('GET', '/'));

    assertSameValue($home->getStatusCode(), 200, 'The skeleton home route failed.');
    assertCondition(
        str_contains($home->getContent(), 'Hello, World!'),
        'The controller and PHP view engine did not render the home page.',
    );

    $cache = $app->make(CacheInterface::class);
    $cache->set('acceptance', 'cache-ready');
    assertSameValue($cache->get('acceptance'), 'cache-ready', 'The cache provider failed.');

    $eventReceived = false;
    $events = $app->make(EventDispatcher::class);
    $event = new class () {};
    $events->listen($event::class, static function () use (&$eventReceived): void {
        $eventReceived = true;
    });
    $events->dispatch($event);
    assertCondition($eventReceived, 'The event dispatcher failed.');

    $session = $app->make('session');
    $session->put('acceptance', 'session-ready');
    assertSameValue($session->get('acceptance'), 'session-ready', 'The session provider failed.');

    $database = $app->make(ConnectionInterface::class);
    $database->statement('CREATE TABLE acceptance_checks (id INTEGER PRIMARY KEY, value TEXT)');
    $database->insert(
        'INSERT INTO acceptance_checks (value) VALUES (?)',
        ['database-ready'],
    );
    assertSameValue(
        $database->selectOne('SELECT value FROM acceptance_checks WHERE id = 1')['value'] ?? null,
        'database-ready',
        'The SQLite database provider failed.',
    );

    $app->post('/acceptance/csrf', static fn (): array => ['accepted' => true]);

    $rejected = $app->handle(new Request('POST', '/acceptance/csrf'));
    assertSameValue($rejected->getStatusCode(), 419, 'CSRF middleware did not reject an invalid request.');

    $token = $app->make(CsrfTokenManager::class)->token();
    $accepted = $app->handle(new Request(
        'POST',
        '/acceptance/csrf',
        body: ['_token' => $token],
    ));

    assertSameValue($accepted->getStatusCode(), 200, 'CSRF middleware rejected a valid request.');
    assertSameValue(
        json_decode($accepted->getContent(), true, flags: JSON_THROW_ON_ERROR),
        ['accepted' => true],
        'The accepted route response was not normalized to JSON.',
    );

    $app->get('/acceptance/form', static function (): Response {
        $emailErrors = errors('email');

        return Response::json([
            'name' => old('name'),
            'email_errors' => is_array($emailErrors) ? $emailErrors : [],
        ]);
    });
    $app->post('/acceptance/form/submit', static function (Request $request) use ($app): array {
        $validator = $app->make(Validator::class);
        if (!$validator instanceof Validator) {
            throw new RuntimeException('The validator service did not resolve.');
        }

        $validated = $validator->validateOrFail($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        return [
            'accepted' => true,
            'name' => $validated['name'] ?? null,
        ];
    });

    $invalid = $app->handle(new Request(
        'POST',
        '/acceptance/form/submit',
        body: [
            '_token' => $token,
            'name' => 'Annabel',
        ],
        headers: [
            'Referer' => '/acceptance/form',
        ],
    ));

    assertSameValue($invalid->getStatusCode(), 302, 'Validation did not redirect back for a web request.');
    assertSameValue($invalid->getHeader('Location'), ['/acceptance/form'], 'Validation redirect target was not preserved.');

    $form = $app->handle(new Request('GET', '/acceptance/form'));
    assertSameValue($form->getStatusCode(), 200, 'The validation form state route failed.');
    assertSameValue(
        json_decode($form->getContent(), true, flags: JSON_THROW_ON_ERROR),
        [
            'name' => 'Annabel',
            'email_errors' => ['The email field is required.'],
        ],
        'Validation errors and old input were not flashed into the session.',
    );

    $valid = $app->handle(new Request(
        'POST',
        '/acceptance/form/submit',
        body: [
            '_token' => $token,
            'name' => 'Annabel',
            'email' => 'hello@example.com',
        ],
        headers: [
            'Accept' => 'application/json',
        ],
    ));

    assertSameValue($valid->getStatusCode(), 200, 'Valid form submission did not succeed.');
    assertSameValue(
        json_decode($valid->getContent(), true, flags: JSON_THROW_ON_ERROR),
        [
            'accepted' => true,
            'name' => 'Annabel',
        ],
        'Valid form submission was not normalized to JSON.',
    );

    $missing = $app->handle(new Request('GET', '/missing'));
    assertSameValue($missing->getStatusCode(), 404, 'The framework did not produce a 404 response.');

    fwrite(STDOUT, "Annabel ecosystem acceptance suite passed.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf(
        "Annabel ecosystem acceptance suite failed: %s\n%s\n",
        $exception->getMessage(),
        $exception->getTraceAsString(),
    ));

    exit(1);
} finally {
    Application::resetInstance();
}
