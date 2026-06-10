<?php

use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\QueueInterface;
use Codemonster\Queue\JobResult;
use Codemonster\Queue\QueueManager;

if (!function_exists('queue')) {
    function queue(?string $connection = null): QueueManager|QueueInterface
    {
        /** @var QueueManager $manager */
        $manager = app(QueueManager::class);

        return $connection === null ? $manager : $manager->connection($connection);
    }
}

if (!function_exists('dispatch')) {
    function dispatch(JobInterface $job, ?string $connection = null, ?string $queue = null): JobResult
    {
        /** @var QueueManager $manager */
        $manager = app(QueueManager::class);

        return $manager->connection($connection)->push($job, $queue);
    }
}
