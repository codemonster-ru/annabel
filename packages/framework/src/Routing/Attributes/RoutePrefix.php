<?php

namespace Codemonster\Annabel\Routing\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class RoutePrefix
{
    public function __construct(public string $prefix)
    {
    }
}
