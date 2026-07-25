<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('site_settings', function (Blueprint $table) {
            $table->dropColumn('site_description');
        });
    }

    public function down(): void
    {
        schema()->table('site_settings', function (Blueprint $table) {
            $table->text('site_description')->nullable();
        });
    }
};
