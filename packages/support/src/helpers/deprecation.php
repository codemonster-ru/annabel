<?php

if (!function_exists('deprecate')) {
    function deprecate(string $package, string $version, string $message, bool|float|int|string|null ...$args): void
    {
        if ($package === '' || $version === '' || $message === '') {
            throw new InvalidArgumentException('Deprecation package, version, and message must be non-empty strings.');
        }

        if ($args !== []) {
            $message = sprintf($message, ...$args);
        }

        @trigger_error(sprintf('Since %s %s: %s', $package, $version, $message), E_USER_DEPRECATED);
    }
}
