<?php

namespace Codemonster\Cms\Modules\Setup\Services;

use Codemonster\Env\Env;

class EnvironmentFile
{
    public function __construct(
        private string $path,
    ) {
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function write(array $values): void
    {
        Env::write($this->path, $values);
    }
}
