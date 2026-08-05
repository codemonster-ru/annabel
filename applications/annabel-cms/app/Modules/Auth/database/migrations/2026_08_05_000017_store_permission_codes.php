<?php

use Codemonster\Cms\Modules\Auth\Services\PermissionRegistry;
use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('role_permission_codes', static function (Blueprint $table): void {
            $table->integer('role_id')->unsigned();
            $table->string('permission', 120);
            $table->primary(['role_id', 'permission']);
            $table->index('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        $permissionCodes = [];
        foreach (db()->table('permissions')->get() as $permission) {
            $permissionCodes[(int) $permission['id']] = (string) $permission['code'];
        }

        foreach (db()->table('role_permission')->get() as $assignment) {
            $code = $permissionCodes[(int) $assignment['permission_id']] ?? null;
            if ($code !== null) {
                db()->table('role_permission_codes')->insert([
                    'role_id' => $assignment['role_id'],
                    'permission' => $code,
                ]);
            }
        }

        schema()->drop('role_permission');
        schema()->table('role_permission_codes', static function (Blueprint $table): void {
            $table->rename('role_permission');
        });
        schema()->drop('permissions');
    }

    public function down(): void
    {
        schema()->create('permissions', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('name', 120);
            $table->string('category', 60);
            $table->integer('sort_order')->unsigned();
            $table->timestamps();
        });

        $registry = app(PermissionRegistry::class);
        if (!$registry instanceof PermissionRegistry) {
            throw new RuntimeException('Permission registry is not available.');
        }

        $now = gmdate('Y-m-d H:i:s');
        foreach ($registry->all() as $permission) {
            db()->table('permissions')->insert($permission + ['created_at' => $now, 'updated_at' => $now]);
        }

        schema()->create('role_permission_ids', static function (Blueprint $table): void {
            $table->integer('role_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->primary(['role_id', 'permission_id']);
            $table->index('role_id');
            $table->index('permission_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        $permissionIds = [];
        foreach (db()->table('permissions')->get() as $permission) {
            $permissionIds[(string) $permission['code']] = $permission['id'];
        }
        foreach (db()->table('role_permission')->get() as $assignment) {
            $permissionId = $permissionIds[(string) $assignment['permission']] ?? null;
            if ($permissionId !== null) {
                db()->table('role_permission_ids')->insert([
                    'role_id' => $assignment['role_id'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        schema()->drop('role_permission');
        schema()->table('role_permission_ids', static function (Blueprint $table): void {
            $table->rename('role_permission');
        });
    }
};
