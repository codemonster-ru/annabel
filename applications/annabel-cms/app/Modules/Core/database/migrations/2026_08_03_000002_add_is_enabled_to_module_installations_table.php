<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('module_installations', function (Blueprint $table): void {
            $table->boolean('is_enabled')->default(true);
        });
    }

    public function down(): void
    {
        schema()->table('module_installations', function (Blueprint $table): void {
            $table->dropColumn('is_enabled');
        });
    }
};
