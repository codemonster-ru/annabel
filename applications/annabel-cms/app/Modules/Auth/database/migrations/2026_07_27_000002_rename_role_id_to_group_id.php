<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('group_user', static function (Blueprint $table): void {
            $table->renameColumn('role_id', 'group_id');
        });
    }

    public function down(): void
    {
        schema()->table('group_user', static function (Blueprint $table): void {
            $table->renameColumn('group_id', 'role_id');
        });
    }
};
