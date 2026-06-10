<?php

namespace Codemonster\Annabel\Routing\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Route
{
    /**
     * @param string|list<string> $methods
     * @param string|list<string|array<mixed>> $middleware
     * @param array<string, string> $where
     */
    public function __construct(
        public string $path,
        public string|array $methods = ['GET'],
        public ?string $name = null,
        public string|array $middleware = [],
        public array $where = [],
    ) {
    }
}
