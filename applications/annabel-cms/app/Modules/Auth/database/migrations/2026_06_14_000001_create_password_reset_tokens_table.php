<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at');

            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        schema()->dropIfExists('password_reset_tokens');
    }
};
