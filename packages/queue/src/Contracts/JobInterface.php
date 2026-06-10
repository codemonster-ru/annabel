<?php

namespace Codemonster\Queue\Contracts;

interface JobInterface
{
    public function handle(): void;
}
