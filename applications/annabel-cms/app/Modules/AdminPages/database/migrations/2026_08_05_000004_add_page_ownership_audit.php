<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->dropForeign('pages_author_id_foreign');
        });
        schema()->table('pages', static function (Blueprint $table): void {
            $table->dropIndex('pages_author_id_index');
        });
        schema()->table('pages', static function (Blueprint $table): void {
            $table->renameColumn('author_id', 'created_by');
        });
        schema()->table('pages', static function (Blueprint $table): void {
            $table->integer('owner_id')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->index('created_by');
            $table->index('owner_id');
            $table->index('updated_by');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        foreach (db()->table('pages')->get() as $page) {
            db()->table('pages')->where('id', $page['id'])->update([
                'owner_id' => $page['created_by'],
                'updated_by' => $page['created_by'],
            ]);
        }
    }

    public function down(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->dropForeign('pages_created_by_foreign');
            $table->dropForeign('pages_owner_id_foreign');
            $table->dropForeign('pages_updated_by_foreign');
        });
        schema()->table('pages', static function (Blueprint $table): void {
            $table->dropIndex('pages_created_by_index');
            $table->dropIndex('pages_owner_id_index');
            $table->dropIndex('pages_updated_by_index');
        });
        schema()->table('pages', static function (Blueprint $table): void {
            $table->dropColumn('owner_id');
            $table->dropColumn('updated_by');
            $table->renameColumn('created_by', 'author_id');
        });
        schema()->table('pages', static function (Blueprint $table): void {
            $table->index('author_id');
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
