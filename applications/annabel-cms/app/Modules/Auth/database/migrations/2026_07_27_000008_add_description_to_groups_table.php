<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('groups', static function (Blueprint $table): void {
            $table->string('description', 500)->nullable();
        });
    }

    public function down(): void
    {
        schema()->table('groups', static function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
