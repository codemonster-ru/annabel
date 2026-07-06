<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create($this->tableName(), function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->integer('attempts')->default(0);
            $table->integer('expires_at')->default(0);
        });
    }

    public function down(): void
    {
        schema()->dropIfExists($this->tableName());
    }

    private function tableName(): string
    {
        if (!function_exists('config')) {
            return 'throttle_requests';
        }

        $tableName = config('security.throttle.table', 'throttle_requests');

        return is_string($tableName) && $tableName !== '' ? $tableName : 'throttle_requests';
    }
};
