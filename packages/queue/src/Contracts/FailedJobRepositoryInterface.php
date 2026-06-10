<?php

namespace Codemonster\Queue\Contracts;

use Codemonster\Queue\FailedJob;

interface FailedJobRepositoryInterface
{
    /** @return list<FailedJob> */
    public function all(): array;

    public function find(string $id): ?FailedJob;

    public function retry(string $id): bool;

    public function retryAll(): int;

    public function forget(string $id): bool;

    public function flush(): int;
}
