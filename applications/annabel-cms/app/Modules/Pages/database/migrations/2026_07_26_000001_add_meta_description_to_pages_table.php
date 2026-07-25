<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('pages', function (Blueprint $table) {
            $table->text('meta_description')->nullable();
        });
    }

    public function down(): void
    {
        schema()->table('pages', function (Blueprint $table) {
            $table->dropColumn('meta_description');
        });
    }
};
