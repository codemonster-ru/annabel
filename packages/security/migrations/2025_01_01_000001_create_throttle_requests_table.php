<?php

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $tableName = function_exists('config')
            ? (string) (config('security.throttle.table', 'throttle_requests'))
            : 'throttle_requests';

        schema()->create($tableName, function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->integer('attempts')->default(0);
            $table->integer('expires_at')->default(0);
        });
    }

    public function down(): void
    {
        $tableName = function_exists('config')
            ? (string) (config('security.throttle.table', 'throttle_requests'))
            : 'throttle_requests';

        schema()->dropIfExists($tableName);
    }
};
