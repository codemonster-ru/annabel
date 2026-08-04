<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->renameColumn('is_published', 'is_active');
            $table->renameColumn('published_at', 'activated_at');
            $table->renameColumn('publish_at', 'active_from');
            $table->renameColumn('unpublish_at', 'active_until');

            $table->index('is_active');
            $table->index('activated_at');
            $table->index('active_from');
            $table->index('active_until');

            $table->dropIndex('pages_is_published_index');
            $table->dropIndex('pages_published_at_index');
            $table->dropIndex('pages_publish_at_index');
            $table->dropIndex('pages_unpublish_at_index');
        });
    }

    public function down(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->renameColumn('is_active', 'is_published');
            $table->renameColumn('activated_at', 'published_at');
            $table->renameColumn('active_from', 'publish_at');
            $table->renameColumn('active_until', 'unpublish_at');

            $table->index('is_published');
            $table->index('published_at');
            $table->index('publish_at');
            $table->index('unpublish_at');

            $table->dropIndex('pages_is_active_index');
            $table->dropIndex('pages_activated_at_index');
            $table->dropIndex('pages_active_from_index');
            $table->dropIndex('pages_active_until_index');
        });
    }
};
