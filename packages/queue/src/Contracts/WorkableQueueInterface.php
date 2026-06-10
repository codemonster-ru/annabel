<?php

namespace Codemonster\Queue\Contracts;

use Codemonster\Queue\QueuedJob;

interface WorkableQueueInterface extends QueueInterface
{
    public function pop(?string $queue = null): ?QueuedJob;
}
