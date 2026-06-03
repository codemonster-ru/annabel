<?php

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        schema()->drop('users');
    }
};
