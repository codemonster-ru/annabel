<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('roles', static function (Blueprint $table): void {
            $table->rename('groups');
        });
        schema()->table('role_user', static function (Blueprint $table): void {
            $table->rename('group_user');
        });
    }

    public function down(): void
    {
        schema()->table('group_user', static function (Blueprint $table): void {
            $table->rename('role_user');
        });
        schema()->table('groups', static function (Blueprint $table): void {
            $table->rename('roles');
        });
    }
};
