<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('permissions', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('name', 120);
            $table->string('category', 60);
            $table->integer('sort_order')->unsigned();
            $table->timestamps();
        });

        schema()->create('group_permission', static function (Blueprint $table): void {
            $table->integer('group_id')->unsigned();
            $table->integer('permission_id')->unsigned();

            $table->primary(['group_id', 'permission_id']);
            $table->index('group_id');
            $table->index('permission_id');

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->cascadeOnDelete();

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();
        });

    }

    public function down(): void
    {
        schema()->dropIfExists('group_permission');
        schema()->dropIfExists('permissions');
    }
};
