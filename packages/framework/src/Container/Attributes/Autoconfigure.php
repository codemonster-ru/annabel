<?php

namespace Codemonster\Annabel\Container\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Autoconfigure
{
    public function __construct(public bool $singleton = false)
    {
    }
}
