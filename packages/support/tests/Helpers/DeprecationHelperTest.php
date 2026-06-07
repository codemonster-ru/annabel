<?php

namespace Codemonster\Support\Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DeprecationHelperTest extends TestCase
{
    public function testDeprecateTriggersStandardNotice(): void
    {
        $message = null;

        set_error_handler(static function (int $severity, string $error) use (&$message): bool {
            if ($severity === E_USER_DEPRECATED) {
                $message = $error;
                return true;
            }

            return false;
        });

        try {
            deprecate('codemonster-ru/support', '1.5', 'Use "%s" instead.', 'new_api');
        } finally {
            restore_error_handler();
        }

        $this->assertSame(
            'Since codemonster-ru/support 1.5: Use "new_api" instead.',
            $message
        );
    }

    public function testDeprecateRejectsIncompleteMetadata(): void
    {
        $this->expectException(InvalidArgumentException::class);

        deprecate('', '1.5', 'Use another API.');
    }
}
