<?php

namespace Codemonster\Queue;

use Codemonster\Queue\Contracts\JobInterface;

class JobSerializer
{
    public function serialize(JobInterface $job): string
    {
        return serialize($job);
    }

    public function deserialize(string $payload): JobInterface
    {
        $job = unserialize($payload, ['allowed_classes' => true]);

        if (!$job instanceof JobInterface) {
            throw new QueueException('Queued payload does not contain a valid job.');
        }

        return $job;
    }
}
