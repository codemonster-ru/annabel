<?php

namespace Codemonster\Annabel\Container\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Service
{
    public function __construct(
        public ?string $as = null,
        public bool $singleton = false,
    ) {
    }
}
