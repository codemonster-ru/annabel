<?php

namespace Codemonster\Queue\Contracts;

interface JobOptionsInterface extends JobInterface
{
    public function maxAttempts(): int;

    /** @return int|list<int> */
    public function backoff(): int|array;

    public function timeout(): int;
}
