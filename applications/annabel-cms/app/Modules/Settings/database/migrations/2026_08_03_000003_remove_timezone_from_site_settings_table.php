<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }

    public function down(): void
    {
        schema()->table('site_settings', function (Blueprint $table): void {
            $table->string('timezone', 64)->default('UTC');
        });
    }
};
