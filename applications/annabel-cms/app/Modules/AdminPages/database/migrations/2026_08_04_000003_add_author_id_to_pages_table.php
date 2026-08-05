<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->integer('author_id')->unsigned()->nullable();
            $table->index('author_id');
            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->dropForeign('pages_author_id_foreign');
            $table->dropColumn('author_id');
        });
    }
};
