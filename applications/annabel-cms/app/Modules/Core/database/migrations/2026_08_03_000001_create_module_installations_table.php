<?php

use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->create('module_installations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->timestamp('installed_at');
        });

        $installedAt = gmdate('Y-m-d H:i:s');

        foreach (app(ModuleManager::class)->definitions() as $module) {
            db()->table('module_installations')->insert([
                'name' => $module->name,
                'installed_at' => $installedAt,
            ]);
        }
    }

    public function down(): void
    {
        schema()->dropIfExists('module_installations');
    }
};
