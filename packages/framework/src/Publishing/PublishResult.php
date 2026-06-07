<?php

namespace Codemonster\Annabel\Publishing;

class PublishResult
{
    /**
     * @param list<string> $published
     * @param list<string> $skipped
     */
    public function __construct(
        public readonly array $published,
        public readonly array $skipped
    ) {}
}
