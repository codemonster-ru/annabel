<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('users', function (Blueprint $table): void {
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();

            $table->index('active_from');
            $table->index('active_until');
        });
    }

    public function down(): void
    {
        schema()->table('users', function (Blueprint $table): void {
            $table->dropIndex('users_active_from_index');
            $table->dropIndex('users_active_until_index');
            $table->dropColumn('active_from');
            $table->dropColumn('active_until');
        });
    }
};
