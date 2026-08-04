<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('group_user', static function (Blueprint $table): void {
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
        });
    }

    public function down(): void
    {
        schema()->table('group_user', static function (Blueprint $table): void {
            $table->dropColumn('active_from');
            $table->dropColumn('active_until');
        });
    }
};
