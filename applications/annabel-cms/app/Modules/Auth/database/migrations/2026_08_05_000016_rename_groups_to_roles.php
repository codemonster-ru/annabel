<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('groups', static function (Blueprint $table): void {
            $table->rename('roles');
        });
        schema()->table('group_user', static function (Blueprint $table): void {
            $table->rename('role_user');
        });
        schema()->table('role_user', static function (Blueprint $table): void {
            $table->renameColumn('group_id', 'role_id');
        });
        schema()->table('group_permission', static function (Blueprint $table): void {
            $table->rename('role_permission');
        });
        schema()->table('role_permission', static function (Blueprint $table): void {
            $table->renameColumn('group_id', 'role_id');
        });

        foreach (['view', 'create', 'update', 'delete'] as $action) {
            db()->table('permissions')
                ->where('code', "groups.{$action}")
                ->update([
                    'code' => "roles.{$action}",
                    'category' => 'Roles',
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ]);
        }
    }

    public function down(): void
    {
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            db()->table('permissions')
                ->where('code', "roles.{$action}")
                ->update([
                    'code' => "groups.{$action}",
                    'category' => 'Groups',
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ]);
        }

        schema()->table('role_permission', static function (Blueprint $table): void {
            $table->renameColumn('role_id', 'group_id');
        });
        schema()->table('role_permission', static function (Blueprint $table): void {
            $table->rename('group_permission');
        });
        schema()->table('role_user', static function (Blueprint $table): void {
            $table->renameColumn('role_id', 'group_id');
        });
        schema()->table('role_user', static function (Blueprint $table): void {
            $table->rename('group_user');
        });
        schema()->table('roles', static function (Blueprint $table): void {
            $table->rename('groups');
        });
    }
};
