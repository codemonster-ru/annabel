<?php

namespace Codemonster\Annabel\Tests\Console;

use Codemonster\Annabel\Console\ArgvInput;
use PHPUnit\Framework\TestCase;

class ArgvInputTest extends TestCase
{
    public function test_it_parses_arguments_and_options()
    {
        $input = new ArgvInput([
            'annabel',
            'example:run',
            'first',
            '--tag=config',
            '--force',
            '--provider',
            'ExampleProvider',
        ]);

        $this->assertSame('example:run', $input->command());
        $this->assertSame(['first'], $input->arguments());
        $this->assertSame('config', $input->option('tag'));
        $this->assertTrue($input->hasOption('force'));
        $this->assertTrue($input->option('force'));
        $this->assertSame('ExampleProvider', $input->option('provider'));
        $this->assertSame([
            'first',
            '--tag=config',
            '--force',
            '--provider',
            'ExampleProvider',
        ], $input->tokens());
    }
}
