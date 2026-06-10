<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue');
            $table->longText('payload');
            $table->integer('attempts');
            $table->integer('max_attempts');
            $table->integer('reserved_at')->nullable();
            $table->integer('available_at');
            $table->integer('created_at');
            $table->index('queue');
            $table->index('available_at');
        });
    }

    public function down(): void
    {
        schema()->drop('jobs');
    }
};
