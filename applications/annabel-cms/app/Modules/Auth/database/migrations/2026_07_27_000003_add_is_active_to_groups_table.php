<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('groups', static function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        schema()->table('groups', static function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
