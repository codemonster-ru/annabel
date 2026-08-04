<?php

use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('module_installations', function (Blueprint $table): void {
            $table->string('installed_version', 64)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        $updatedAt = gmdate('Y-m-d H:i:s');
        $modules = app(ModuleManager::class)->availableDefinitions();

        foreach (db()->table('module_installations')->get() as $installation) {
            $name = $installation['name'] ?? null;
            $module = is_string($name) ? ($modules[$name] ?? null) : null;

            if ($module === null) {
                continue;
            }

            db()->table('module_installations')
                ->where('name', $name)
                ->update([
                    'installed_version' => $module->version,
                    'updated_at' => $updatedAt,
                ]);
        }
    }

    public function down(): void
    {
        schema()->table('module_installations', function (Blueprint $table): void {
            $table->dropColumn('installed_version');
            $table->dropColumn('updated_at');
        });
    }
};
