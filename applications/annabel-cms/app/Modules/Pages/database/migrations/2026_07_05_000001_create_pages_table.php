<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title');
            $table->longText('content');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        schema()->dropIfExists('pages');
    }
};
