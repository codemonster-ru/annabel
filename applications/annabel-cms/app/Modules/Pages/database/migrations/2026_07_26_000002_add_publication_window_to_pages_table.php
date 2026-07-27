<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('pages', function (Blueprint $table) {
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('unpublish_at')->nullable();

            $table->index('publish_at');
            $table->index('unpublish_at');
        });
    }

    public function down(): void
    {
        schema()->table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_publish_at_index');
            $table->dropIndex('pages_unpublish_at_index');
            $table->dropColumn('publish_at');
            $table->dropColumn('unpublish_at');
        });
    }
};
