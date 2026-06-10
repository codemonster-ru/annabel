# Codemonster Queue

Queue and job primitives for Annabel applications.

## Usage

```php
use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\QueueManager;

final class SendWelcomeEmail implements JobInterface
{
    public function handle(): void
    {
        // Send the email.
    }
}

$manager = new QueueManager([
    'default' => 'sync',
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
    ],
]);

$manager->connection()->push(new SendWelcomeEmail());
```

The package ships with `sync`, `database`, and `redis` drivers. The Redis
driver uses the PHP Redis extension when no explicit client object is
configured:

```php
$manager = new QueueManager([
    'default' => 'redis',
    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'prefix' => 'queue:',
            'retry_after' => 60,
            'max_attempts' => 3,
        ],
    ],
]);
```

## Database Driver

The database driver stores serialized jobs in a `jobs` table and can be
processed by `Worker`:

```php
use Codemonster\Queue\Contracts\WorkableQueueInterface;
use Codemonster\Queue\Worker;

/** @var WorkableQueueInterface $queue */
$queue = $manager->connection('database');
$queue->push(new SendWelcomeEmail());

(new Worker($queue))->workOnce();
```

Jobs can override their retry policy and timeout through
`JobOptionsInterface`:

```php
use Codemonster\Queue\Contracts\JobOptionsInterface;

final class SendWelcomeEmail implements JobOptionsInterface
{
    public function handle(): void
    {
        // Send the email.
    }

    public function maxAttempts(): int
    {
        return 5;
    }

    public function backoff(): int|array
    {
        return [10, 30, 120];
    }

    public function timeout(): int
    {
        return 30;
    }
}
```

Timeouts require the PCNTL extension. A backoff array uses the delay matching
the current attempt and keeps using its last value for later attempts.

Failed jobs stored by the database driver can be inspected and retried:

```php
$failed = $manager->failedJobs('database');

foreach ($failed->all() as $job) {
    echo $job->id() . ': ' . $job->exception();
}

$failed->retry('1');
$failed->retryAll();
$failed->flush();
```

Jobs should contain serializable data. Runtime services such as PDO connections
should be resolved inside `handle()` by the application container or another
application-level dependency mechanism.
