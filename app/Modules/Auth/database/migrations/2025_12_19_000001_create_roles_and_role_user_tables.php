<?php

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        schema()->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        schema()->create('role_user', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->primary(['user_id', 'role_id']);
            $table->index('user_id');
            $table->index('role_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
        });

    }

    public function down(): void
    {
        schema()->dropIfExists('role_user');
        schema()->dropIfExists('roles');
    }
};
