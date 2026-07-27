<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('admin_table_preferences', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('table_key', 120);
            $table->json('visible_columns');
            $table->timestamps();

            $table->unique(['user_id', 'table_key']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        schema()->dropIfExists('admin_table_preferences');
    }
};
