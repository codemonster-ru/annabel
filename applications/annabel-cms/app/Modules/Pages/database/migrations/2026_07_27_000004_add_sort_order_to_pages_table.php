<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->integer('sort_order')->default(1);
        });
    }

    public function down(): void
    {
        schema()->table('pages', static function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
