<?php

namespace Codemonster\Database\Tests\Schema;

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Schema\Grammars\MySqlGrammar;
use Codemonster\Database\Tests\TestCase;

class AlterTableTest extends TestCase
{
    public function test_compile_alter_generates_statements(): void
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->string('name');
        $blueprint->integer('age')->change();
        $blueprint->renameColumn('name', 'full_name');
        $blueprint->dropColumn('legacy');
        $blueprint->dropIndex('users_legacy_index');
        $blueprint->dropPrimary();
        $blueprint->dropForeign('users_role_id_foreign');
        $blueprint->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
        $blueprint->index(['name', 'age']);

        $sqls = $grammar->compileAlter($blueprint);
        $joined = implode("\n", $sqls);

        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN `name` VARCHAR(255)', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` MODIFY COLUMN `age` INT', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` RENAME COLUMN `name` TO `full_name`', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` DROP COLUMN `legacy`', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` DROP INDEX `users_legacy_index`', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` DROP PRIMARY KEY', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` DROP FOREIGN KEY `users_role_id_foreign`', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` ADD CONSTRAINT `users_role_id_foreign`', $joined);
        $this->assertStringContainsString('ALTER TABLE `users` ADD INDEX `users_name_age_index` (`name`, `age`)', $joined);
    }

    public function test_compile_rename_table(): void
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->rename('accounts');

        $sqls = $grammar->compileAlter($blueprint);

        $this->assertSame(['RENAME TABLE `users` TO `accounts`'], $sqls);
    }
}
