<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->text('exception')->nullable();
            $table->integer('failed_at');
            $table->index('queue');
            $table->index('failed_at');
        });
    }

    public function down(): void
    {
        schema()->drop('failed_jobs');
    }
};
