<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Schema\MySqlGrammar;
use Codemonster\Database\Tests\TestCase;

class CreateTableTest extends TestCase
{
    public function test_create_table_grammar()
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->id();
        $blueprint->string('name');
        $blueprint->timestamps();

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('CREATE TABLE `users`', $sql[0]);
        $this->assertStringContainsString('`id` INT UNSIGNED AUTO_INCREMENT NOT NULL', $sql[0]);
    }

    public function test_id_and_timestamps_columns()
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->id();
        $blueprint->timestamps();

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql[0]);
        $this->assertStringContainsString('`created_at` TIMESTAMP', $sql[0]);
        $this->assertStringContainsString('`updated_at` TIMESTAMP', $sql[0]);
    }

    public function test_custom_id_name()
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('widgets');

        $blueprint->id('widget_id');

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('`widget_id` INT UNSIGNED AUTO_INCREMENT NOT NULL', $sql[0]);
        $this->assertStringContainsString('PRIMARY KEY (`widget_id`)', $sql[0]);
    }

    public function test_timestamps_nullable_by_default()
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->timestamps();

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('`created_at` TIMESTAMP', $sql[0]);
        $this->assertStringContainsString('`updated_at` TIMESTAMP', $sql[0]);
        $this->assertStringNotContainsString('`created_at` TIMESTAMP NOT NULL', $sql[0]);
        $this->assertStringNotContainsString('`updated_at` TIMESTAMP NOT NULL', $sql[0]);
    }

    public function test_create_table_with_foreign_key_and_index()
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('posts');

        $blueprint->id();
        $blueprint->integer('user_id');
        $blueprint->foreign('user_id')
            ->references('id')
            ->on('users')
            ->cascadeOnDelete();
        $blueprint->index('user_id');

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE', $sql[0]);
        $this->assertStringContainsString('INDEX `posts_user_id_index` (`user_id`)', $sql[0]);
    }
}
