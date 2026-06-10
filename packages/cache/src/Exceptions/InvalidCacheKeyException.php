<?php

namespace Codemonster\Cache\Exceptions;

use Psr\SimpleCache\InvalidArgumentException;

class InvalidCacheKeyException extends \InvalidArgumentException implements InvalidArgumentException
{
}
