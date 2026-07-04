<?php

namespace Codemonster\Cms\Tests\Unit\Core;

use Codemonster\Cms\Modules\Core\ModuleDefinition;
use PHPUnit\Framework\TestCase;

class ModuleDefinitionTest extends TestCase
{
    public function testItResolvesModuleRelativePaths(): void
    {
        $module = new ModuleDefinition('Demo', '1.0.0', '/cms/app/Modules/Demo');

        self::assertSame('/cms/app/Modules/Demo/routes/web.php', $module->resolve('routes/web.php'));
        self::assertNull($module->resolve(null));
    }

    public function testItRejectsPathsOutsideModuleDirectory(): void
    {
        $module = new ModuleDefinition('Demo', '1.0.0', '/cms/app/Modules/Demo');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Module path must stay inside module [Demo]');

        $module->resolve('../Setup/routes/web.php');
    }
}
