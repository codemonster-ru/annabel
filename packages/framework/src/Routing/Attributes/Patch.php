<?php

namespace Codemonster\Annabel\Routing\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Patch extends Route
{
    /** @param string|list<string|array<mixed>> $middleware */
    public function __construct(string $path, ?string $name = null, string|array $middleware = [], array $where = [])
    {
        parent::__construct($path, ['PATCH'], $name, $middleware, $where);
    }
}
