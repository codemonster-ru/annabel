<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('pages', function (Blueprint $table): void {
            $table->string('meta_title', 255)->nullable();
        });
    }

    public function down(): void
    {
        schema()->table('pages', function (Blueprint $table): void {
            $table->dropColumn('meta_title');
        });
    }
};
