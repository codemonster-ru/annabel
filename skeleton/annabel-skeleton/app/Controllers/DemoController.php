<?php

namespace App\Controllers;

use Codemonster\Annabel\Http\Exceptions\NotFoundHttpException;
use Codemonster\ApiResource\JsonResource;
use Codemonster\Auth\Contracts\PasswordHasherInterface;
use Codemonster\Config\Config;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Errors\Contracts\ExceptionHandlerInterface;
use Codemonster\Events\EventDispatcher;
use Codemonster\Filesystem\Contracts\FilesystemInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\HttpClient\Contracts\TransportInterface;
use Codemonster\HttpClient\HttpClient;
use Codemonster\HttpClient\HttpResponse;
use Codemonster\HttpClient\RequestData;
use Codemonster\Mail\Contracts\MailerInterface;
use Codemonster\Mail\Message;
use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\QueueInterface;
use Codemonster\Scheduler\Schedule;
use Codemonster\Validation\Validator;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

class DemoController
{
    public function index(): Response
    {
        return $this->view();
    }

    public function cache(CacheInterface $cache): Response
    {
        $value = 'cache-' . date('H:i:s');
        $cache->set('annabel.demo.cache', $value);

        return $this->view([
            'notice' => 'Cache wrote and read the value successfully.',
            'cacheValue' => $cache->get('annabel.demo.cache'),
        ]);
    }

    public function session(Request $request): Response
    {
        $session = app('session');
        $count = (int) $session->get('demo.count', 0) + 1;
        $session->put('demo.count', $count);
        $cookies = $request->getCookieParams();

        return $this->view([
            'notice' => 'Session state was updated for this browser.',
            'sessionCount' => $count,
            'sessionId' => substr((string) ($cookies['SESSION_ID'] ?? ''), 0, 12),
        ]);
    }

    public function validation(Request $request, Validator $validator): Response
    {
        $result = $validator->validate($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        return $this->view([
            'notice' => $result->passes()
                ? 'Validation passed and returned clean data.'
                : 'Validation caught the expected input problem.',
            'validationErrors' => $result->errors(),
            'validated' => $result->validated(),
            'formInput' => [
                'name' => (string) $request->input('name', ''),
                'email' => (string) $request->input('email', ''),
            ],
        ]);
    }

    public function database(ConnectionInterface $database): Response
    {
        try {
            $database->statement(
                'CREATE TABLE IF NOT EXISTS demo_events (
                    id INTEGER PRIMARY KEY AUTO_INCREMENT,
                    label VARCHAR(120) NOT NULL,
                    created_at INTEGER NOT NULL
                )',
            );
            $database->insert(
                'INSERT INTO demo_events (label, created_at) VALUES (?, ?)',
                ['browser-demo', time()],
            );
            $latest = $database->selectOne('SELECT COUNT(*) AS total FROM demo_events');

            return $this->view([
                'notice' => 'Database connection wrote and counted a demo row.',
                'databaseTotal' => (int) ($latest['total'] ?? 0),
            ]);
        } catch (Throwable $exception) {
            return $this->view([
                'notice' => 'Database demo could not run with the current connection settings.',
                'databaseError' => $exception->getMessage(),
            ]);
        }
    }

    public function filesystem(FilesystemInterface $filesystem): Response
    {
        $path = 'demo/status.txt';
        $contents = 'filesystem-' . date('H:i:s');
        $filesystem->put($path, $contents);

        return $this->view([
            'notice' => 'Filesystem disk wrote and read a demo file.',
            'results' => [
                'filesystem' => [
                    'path' => $path,
                    'contents' => $filesystem->get($path),
                    'size' => $filesystem->size($path) . ' bytes',
                ],
            ],
        ]);
    }

    public function events(EventDispatcher $events): Response
    {
        $received = false;
        $event = new class () {};

        $events->listen($event::class, static function () use (&$received): void {
            $received = true;
        });
        $events->dispatch($event);

        return $this->view([
            'notice' => 'Event dispatcher invoked a listener.',
            'results' => [
                'events' => [
                    'listener_received' => $received ? 'yes' : 'no',
                    'event_class' => $event::class,
                ],
            ],
        ]);
    }

    public function queue(QueueInterface $queue): Response
    {
        $result = $queue->push(new class () implements JobInterface {
            public function handle(): void
            {
            }
        }, 'demo');

        return $this->view([
            'notice' => 'Queue accepted a job through the configured connection.',
            'results' => [
                'queue' => [
                    'connection' => $result->connection(),
                    'queue' => $result->queue(),
                    'processed_now' => $result->processed() ? 'yes' : 'no',
                    'job_id' => $result->id(),
                ],
            ],
        ]);
    }

    public function mail(MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $sent = $mailer->send(Message::make()
            ->from('hello@example.com', 'Annabel')
            ->to('demo@example.com', 'Demo')
            ->subject('Annabel demo message')
            ->text('This message is sent through the configured mail transport.'));
        $logger->info('Annabel demo mail was sent.', ['message_id' => $sent->id()]);

        return $this->view([
            'notice' => 'Mailer sent a demo message through the configured transport.',
            'results' => [
                'mail' => [
                    'message_id' => $sent->id(),
                    'mailer' => $sent->mailer(),
                    'transport' => $sent->transport(),
                    'log_channel' => config('logging.default', 'unknown'),
                ],
            ],
        ]);
    }

    public function auth(PasswordHasherInterface $hasher): Response
    {
        $hash = $hasher->make('annabel-demo');

        return $this->view([
            'notice' => 'Auth password hasher created and verified a hash.',
            'results' => [
                'auth' => [
                    'verified' => $hasher->check('annabel-demo', $hash) ? 'yes' : 'no',
                    'needs_rehash' => $hasher->needsRehash($hash) ? 'yes' : 'no',
                    'hash_prefix' => substr($hash, 0, 8),
                ],
            ],
        ]);
    }

    public function config(Config $config, Schedule $schedule): Response
    {
        $schedule->call(static fn () => null, 'demo heartbeat')->everyMinute();

        return $this->view([
            'notice' => 'Configuration and scheduler services resolved successfully.',
            'results' => [
                'config' => [
                    'app_name' => (string) $config::get('app.name', 'Annabel'),
                    'environment' => (string) env('APP_ENV', 'local'),
                    'database' => (string) $config::get('database.default', 'unknown'),
                ],
                'scheduler' => [
                    'tasks' => (string) count($schedule->tasks()),
                    'due_now' => (string) count($schedule->dueTasks()),
                ],
            ],
        ]);
    }

    public function httpClient(): Response
    {
        $client = new HttpClient(new class () implements TransportInterface {
            public function send(RequestData $request): HttpResponse
            {
                return new HttpResponse(200, json_encode([
                    'method' => $request->method(),
                    'url' => $request->url(),
                    'accept' => $request->headers()['Accept'] ?? null,
                ], JSON_THROW_ON_ERROR), ['content-type' => ['application/json']]);
            }
        });
        $response = $client->baseUrl('https://example.test')->acceptJson()->get('/demo', ['source' => 'annabel']);

        return $this->view([
            'notice' => 'HTTP client built and sent a request through a transport.',
            'results' => [
                'http_client' => [
                    'status' => (string) $response->status(),
                    'successful' => $response->successful() ? 'yes' : 'no',
                    'payload' => $response->json(),
                ],
            ],
        ]);
    }

    public function errors(ExceptionHandlerInterface $handler): Response
    {
        $response = $handler->handle(new NotFoundHttpException('Demo missing page'));

        return $this->view([
            'notice' => 'Error handler rendered a framework error response.',
            'results' => [
                'errors' => [
                    'status' => (string) $response->getStatusCode(),
                    'content_type' => implode(', ', $response->getHeader('Content-Type')),
                    'body_preview' => substr(trim(strip_tags($response->getContent())), 0, 80),
                ],
            ],
        ]);
    }

    public function json(): Response
    {
        return Response::json([
            'framework' => 'Annabel',
            'status' => 'ok',
            'packages' => [
                'api-resource',
                'auth',
                'cache',
                'config',
                'database',
                'events',
                'filesystem',
                'http',
                'http-client',
                'logging',
                'mail',
                'queue',
                'router',
                'scheduler',
                'security',
                'session',
                'validation',
                'view',
                'errors',
            ],
            'resource' => (new class ([
                'id' => 1,
                'name' => 'Annabel Demo',
            ]) extends JsonResource {
                public function toArray(): array
                {
                    return $this->resource;
                }
            })->resolve(),
        ]);
    }

    /**
     * @param array<string, mixed> $state
     */
    private function view(array $state = []): Response
    {
        return view('demo', array_replace([
            'notice' => null,
            'cacheValue' => null,
            'sessionCount' => null,
            'sessionId' => null,
            'validationErrors' => [],
            'validated' => [],
            'formInput' => [
                'name' => 'Annabel',
                'email' => '',
            ],
            'databaseTotal' => null,
            'databaseError' => null,
            'results' => [],
        ], $state));
    }
}
