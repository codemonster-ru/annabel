<?php

namespace Codemonster\Annabel\Routing\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Middleware
{
    public function __construct(public string $name, public mixed $parameter = null)
    {
    }
}
