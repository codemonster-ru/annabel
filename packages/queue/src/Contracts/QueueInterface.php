<?php

namespace Codemonster\Queue\Contracts;

use Codemonster\Queue\JobResult;

interface QueueInterface
{
    public function push(JobInterface $job, ?string $queue = null): JobResult;
}
