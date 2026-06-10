<?php

namespace Codemonster\Database\Tests\Schema;

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Schema\Grammars\SQLiteGrammar;
use Codemonster\Database\Tests\TestCase;

class SQLiteGrammarTest extends TestCase
{
    public function test_create_table_grammar()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->string('name')->nullable(false)->unique();
        $blueprint->boolean('active')->default(true);

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('CREATE TABLE "users"', $sql[0]);
        $this->assertStringContainsString('"name" TEXT NOT NULL UNIQUE', $sql[0]);
        $this->assertStringContainsString('"active" INTEGER DEFAULT 1', $sql[0]);
    }

    public function test_id_and_timestamps_columns()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->id();
        $blueprint->timestamps();

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('"id" INTEGER', $sql[0]);
        $this->assertStringContainsString('"created_at" TEXT', $sql[0]);
        $this->assertStringContainsString('"updated_at" TEXT', $sql[0]);
    }

    public function test_custom_id_name()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('widgets');

        $blueprint->id('widget_id');

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('"widget_id" INTEGER', $sql[0]);
        $this->assertStringNotContainsString('PRIMARY KEY ("widget_id")', $sql[0]);
    }

    public function test_timestamps_are_nullable_by_default()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->timestamps();

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('"created_at" TEXT', $sql[0]);
        $this->assertStringContainsString('"updated_at" TEXT', $sql[0]);
        $this->assertStringNotContainsString('"created_at" TEXT NOT NULL', $sql[0]);
        $this->assertStringNotContainsString('"updated_at" TEXT NOT NULL', $sql[0]);
    }

    public function test_foreign_key_on_delete_and_update()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('posts');

        $blueprint->integer('user_id');
        $blueprint->foreign('user_id')
            ->references('id')
            ->on('users')
            ->cascadeOnDelete()
            ->restrictOnUpdate();

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString(
            'FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE RESTRICT',
            $sql[0],
        );
    }

    public function test_alter_table_grammar()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->renameColumn('name', 'full_name');
        $blueprint->string('email');

        $sql = $grammar->compileAlter($blueprint);

        $this->assertStringContainsString('ALTER TABLE "users" RENAME COLUMN "name" TO "full_name"', $sql[0]);
        $this->assertStringContainsString('ALTER TABLE "users" ADD COLUMN', $sql[1]);
    }

    public function test_rename_table_and_drop_statements()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->rename('accounts');

        $rename = $grammar->compileRenameTable($blueprint);
        $drop = $grammar->compileDrop('users');
        $dropIfExists = $grammar->compileDropIfExists('users');

        $this->assertSame('ALTER TABLE "users" RENAME TO "accounts"', $rename[0]);
        $this->assertSame('DROP TABLE "users"', $drop[0]);
        $this->assertSame('DROP TABLE IF EXISTS "users"', $dropIfExists[0]);
    }

    public function test_alter_ignores_drop_operations()
    {
        $grammar = new SQLiteGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->dropColumn('legacy');
        $blueprint->dropIndex('users_legacy_index');
        $blueprint->dropUnique('users_email_unique');
        $blueprint->dropPrimary();
        $blueprint->dropForeign('users_role_id_foreign');

        $sql = $grammar->compileAlter($blueprint);

        $this->assertSame([], $sql);
    }
}
