<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 120);
            $table->text('site_description')->nullable();
            $table->string('locale', 20);
            $table->string('timezone', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        schema()->dropIfExists('site_settings');
    }
};
