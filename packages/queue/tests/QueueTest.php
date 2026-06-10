<?php

namespace Codemonster\Queue\Tests;

use Codemonster\Database\Connection;
use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\JobOptionsInterface;
use Codemonster\Queue\DatabaseQueue;
use Codemonster\Queue\QueueException;
use Codemonster\Queue\QueueManager;
use Codemonster\Queue\QueueTimeoutException;
use Codemonster\Queue\RedisQueue;
use Codemonster\Queue\SyncQueue;
use Codemonster\Queue\Worker;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function test_sync_queue_processes_jobs_immediately(): void
    {
        $job = new TestJob();
        $queue = new SyncQueue();

        $result = $queue->push($job, 'emails');

        self::assertSame(1, $job->runs);
        self::assertSame('sync', $result->connection());
        self::assertSame('emails', $result->queue());
        self::assertTrue($result->processed());
        self::assertNotSame('', $result->id());
    }

    public function test_manager_resolves_configured_connections(): void
    {
        $manager = new QueueManager([
            'default' => 'sync',
            'connections' => [
                'sync' => [
                    'driver' => 'sync',
                ],
            ],
        ]);

        $job = new TestJob();
        $manager->connection()->push($job);

        self::assertSame(['sync'], $manager->connections());
        self::assertSame(1, $job->runs);
    }

    public function test_manager_resolves_redis_connection(): void
    {
        $manager = new QueueManager([
            'default' => 'redis',
            'connections' => [
                'redis' => [
                    'driver' => 'redis',
                    'client' => new FakeRedisQueueClient(),
                    'prefix' => 'test:',
                ],
            ],
        ]);

        self::assertInstanceOf(RedisQueue::class, $manager->connection());
        self::assertSame(['redis'], $manager->connections());
    }

    public function test_unknown_driver_is_rejected(): void
    {
        $manager = new QueueManager([
            'default' => 'database',
            'connections' => [
                'database' => [
                    'driver' => 'database',
                ],
            ],
        ]);

        $this->expectException(QueueException::class);

        $manager->connection();
    }

    public function test_database_queue_stores_and_pops_jobs(): void
    {
        $connection = $this->sqlite();
        $queue = new DatabaseQueue($connection);

        $result = $queue->push(new TestJob(), 'emails');
        $job = $queue->pop('emails');

        self::assertFalse($result->processed());
        self::assertNotNull($job);
        self::assertSame($result->id(), $job->id());
        self::assertSame('emails', $job->queue());
        self::assertSame(1, $job->attempts());
        self::assertSame(3, $job->maxAttempts());
    }

    public function test_worker_processes_database_jobs(): void
    {
        $connection = $this->sqlite();
        $queue = new DatabaseQueue($connection);
        PersistentTestJob::$runs = 0;
        $queue->push(new PersistentTestJob());

        $result = (new Worker($queue))->workOnce();

        self::assertSame('processed', $result->status());
        self::assertCount(0, $connection->table('jobs')->where('queue', 'default')->get());
        self::assertSame(1, PersistentTestJob::$runs);
    }

    public function test_worker_processes_redis_jobs(): void
    {
        $queue = new RedisQueue(new FakeRedisQueueClient());
        PersistentTestJob::$runs = 0;
        $queue->push(new PersistentTestJob());

        $result = (new Worker($queue))->workOnce();

        self::assertSame('processed', $result->status());
        self::assertSame(1, PersistentTestJob::$runs);
        self::assertNull($queue->pop());
    }

    public function test_worker_moves_failed_jobs_after_max_attempts(): void
    {
        $connection = $this->sqlite();
        $queue = new DatabaseQueue($connection, maxAttempts: 1);
        $queue->push(new FailingTestJob());

        $result = (new Worker($queue))->workOnce();

        self::assertSame('failed', $result->status());
        self::assertCount(0, $connection->table('jobs')->where('queue', 'default')->get());
        self::assertCount(1, $connection->table('failed_jobs')->where('queue', 'default')->get());
    }

    public function test_failed_jobs_can_be_listed_retried_and_flushed(): void
    {
        $connection = $this->sqlite();
        $manager = new QueueManager([
            'default' => 'database',
            'connections' => [
                'database' => [
                    'driver' => 'database',
                    'connection' => $connection,
                    'max_attempts' => 5,
                ],
            ],
        ]);
        $queue = $manager->connection();
        self::assertInstanceOf(DatabaseQueue::class, $queue);
        $queue->push(new FailingTestJob());
        $queued = $queue->pop();
        self::assertNotNull($queued);
        $queued->fail(new \RuntimeException('Expected failure.'));

        $failed = $manager->failedJobs();
        $jobs = $failed->all();

        self::assertCount(1, $jobs);
        self::assertSame('database', $jobs[0]->connection());
        self::assertTrue($failed->retry($jobs[0]->id()));
        self::assertSame([], $failed->all());

        $row = $connection->table('jobs')->first();
        self::assertNotNull($row);
        self::assertTrue(in_array($row['attempts'], [0, '0'], true));
        self::assertTrue(in_array($row['max_attempts'], [5, '5'], true));

        $connection->table('failed_jobs')->insert([
            'connection' => 'database',
            'queue' => 'default',
            'payload' => (new \Codemonster\Queue\JobSerializer())->serialize(new TestJob()),
            'exception' => null,
            'failed_at' => time(),
        ]);

        self::assertSame(1, $failed->flush());
        self::assertSame([], $failed->all());
    }

    public function test_redis_failed_jobs_can_be_listed_retried_and_flushed(): void
    {
        $manager = new QueueManager([
            'default' => 'redis',
            'connections' => [
                'redis' => [
                    'driver' => 'redis',
                    'client' => new FakeRedisQueueClient(),
                    'prefix' => 'test:',
                    'max_attempts' => 1,
                ],
            ],
        ]);
        $queue = $manager->connection();
        self::assertInstanceOf(RedisQueue::class, $queue);
        $queue->push(new FailingTestJob(), 'emails');

        $result = (new Worker($queue))->workOnce('emails');
        $failed = $manager->failedJobs();
        $jobs = $failed->all();

        self::assertSame('failed', $result->status());
        self::assertCount(1, $jobs);
        self::assertSame('redis', $jobs[0]->connection());
        self::assertSame('emails', $jobs[0]->queue());
        self::assertTrue($failed->retry($jobs[0]->id()));
        self::assertSame([], $failed->all());
        self::assertNotNull($queue->pop('emails'));

        $queue->push(new FailingTestJob(), 'emails');
        (new Worker($queue))->workOnce('emails');

        self::assertSame(1, $failed->flush());
        self::assertSame([], $failed->all());
    }

    public function test_job_options_override_attempts_and_backoff(): void
    {
        $connection = $this->sqlite();
        $queue = new DatabaseQueue($connection, maxAttempts: 5);
        $queue->push(new ConfiguredFailingTestJob(maxAttempts: 2, backoff: [2, 5]));

        $before = time();
        $result = (new Worker($queue, backoff: 10))->workOnce();
        $row = $connection->table('jobs')->first();

        self::assertSame('failed', $result->status());
        self::assertNotNull($row);
        self::assertTrue(in_array($row['max_attempts'], [2, '2'], true));
        self::assertTrue(is_int($row['available_at']) || is_string($row['available_at']));
        self::assertGreaterThanOrEqual($before + 2, (int) $row['available_at']);
    }

    public function test_worker_times_out_configured_jobs(): void
    {
        if (!function_exists('pcntl_alarm')) {
            self::markTestSkipped('PCNTL is required for timeout testing.');
        }

        $connection = $this->sqlite();
        $queue = new DatabaseQueue($connection);
        $queue->push(new ConfiguredSlowTestJob());

        $result = (new Worker($queue))->workOnce();

        self::assertSame('failed', $result->status());
        self::assertInstanceOf(QueueTimeoutException::class, $result->exception());
        self::assertCount(1, $connection->table('failed_jobs')->get());
    }

    public function test_failed_jobs_are_rejected_for_sync_connections(): void
    {
        $manager = new QueueManager([
            'default' => 'sync',
            'connections' => [
                'sync' => [
                    'driver' => 'sync',
                ],
            ],
        ]);

        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('does not support failed jobs');

        $manager->failedJobs();
    }

    private function sqlite(): Connection
    {
        $connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $connection->statement('CREATE TABLE jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT NOT NULL, payload TEXT NOT NULL, attempts INTEGER NOT NULL, max_attempts INTEGER NOT NULL, reserved_at INTEGER NULL, available_at INTEGER NOT NULL, created_at INTEGER NOT NULL)');
        $connection->statement('CREATE TABLE failed_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, connection TEXT NOT NULL, queue TEXT NOT NULL, payload TEXT NOT NULL, exception TEXT NULL, failed_at INTEGER NOT NULL)');

        return $connection;
    }
}

class TestJob implements JobInterface
{
    public int $runs = 0;

    public function handle(): void
    {
        $this->runs++;
    }
}

class PersistentTestJob implements JobInterface
{
    public static int $runs = 0;

    public function handle(): void
    {
        self::$runs++;
    }
}

class FailingTestJob implements JobInterface
{
    public function handle(): void
    {
        throw new \RuntimeException('Expected failure.');
    }
}

class ConfiguredFailingTestJob implements JobOptionsInterface
{
    /** @param int|list<int> $backoff */
    public function __construct(
        private int $maxAttempts,
        private int|array $backoff,
    ) {
    }

    public function handle(): void
    {
        throw new \RuntimeException('Expected failure.');
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function backoff(): int|array
    {
        return $this->backoff;
    }

    public function timeout(): int
    {
        return 0;
    }
}

class ConfiguredSlowTestJob implements JobOptionsInterface
{
    public function handle(): void
    {
        sleep(2);
    }

    public function maxAttempts(): int
    {
        return 1;
    }

    public function backoff(): int|array
    {
        return 0;
    }

    public function timeout(): int
    {
        return 1;
    }
}

class FakeRedisQueueClient
{
    /** @var array<string, list<string>> */
    private array $lists = [];

    /** @var array<string, array<string, int>> */
    private array $sets = [];

    /** @var array<string, array<string, string>> */
    private array $hashes = [];

    public function rPush(string $key, string $value): int
    {
        $this->lists[$key] ??= [];
        $this->lists[$key][] = $value;

        return count($this->lists[$key]);
    }

    public function lPop(string $key): string|false
    {
        if (($this->lists[$key] ?? []) === []) {
            return false;
        }

        return array_shift($this->lists[$key]) ?: false;
    }

    public function zAdd(string $key, int $score, string $member): int
    {
        $exists = isset($this->sets[$key][$member]);
        $this->sets[$key][$member] = $score;

        return $exists ? 0 : 1;
    }

    /** @return list<string> */
    public function zRangeByScore(string $key, string $min, string $max): array
    {
        $minimum = $min === '-inf' ? PHP_INT_MIN : (int) $min;
        $maximum = $max === '+inf' ? PHP_INT_MAX : (int) $max;
        $items = [];

        foreach ($this->sortedSet($key) as $member => $score) {
            if ($score >= $minimum && $score <= $maximum) {
                $items[] = $member;
            }
        }

        return $items;
    }

    public function zRem(string $key, string $member): int
    {
        $exists = isset($this->sets[$key][$member]);
        unset($this->sets[$key][$member]);

        return $exists ? 1 : 0;
    }

    public function hSet(string $key, string $field, string $value): int
    {
        $exists = isset($this->hashes[$key][$field]);
        $this->hashes[$key][$field] = $value;

        return $exists ? 0 : 1;
    }

    public function hGet(string $key, string $field): string|false
    {
        return $this->hashes[$key][$field] ?? false;
    }

    public function hDel(string $key, string $field): int
    {
        $exists = isset($this->hashes[$key][$field]);
        unset($this->hashes[$key][$field]);

        return $exists ? 1 : 0;
    }

    public function del(string $key): int
    {
        $count = 0;

        if (isset($this->lists[$key])) {
            unset($this->lists[$key]);
            $count++;
        }
        if (isset($this->sets[$key])) {
            unset($this->sets[$key]);
            $count++;
        }
        if (isset($this->hashes[$key])) {
            unset($this->hashes[$key]);
            $count++;
        }

        return $count;
    }

    /** @return list<string> */
    public function zRange(string $key, int $start, int $stop): array
    {
        $members = array_keys($this->sortedSet($key));

        if ($stop < 0) {
            $stop = count($members) + $stop;
        }

        return array_slice($members, $start, $stop - $start + 1);
    }

    /** @return array<string, int> */
    private function sortedSet(string $key): array
    {
        $items = $this->sets[$key] ?? [];
        asort($items);

        return $items;
    }
}
