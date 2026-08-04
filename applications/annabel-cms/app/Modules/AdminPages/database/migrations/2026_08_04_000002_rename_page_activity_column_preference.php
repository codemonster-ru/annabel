<?php

use Codemonster\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        $this->renamePreference('is_published', 'is_active');
    }

    public function down(): void
    {
        $this->renamePreference('is_active', 'is_published');
    }

    private function renamePreference(string $from, string $to): void
    {
        $preferences = db()->table('admin_table_preferences')
            ->where('table_key', 'pages')
            ->get();

        foreach ($preferences as $preference) {
            $columns = json_decode((string) ($preference['visible_columns'] ?? ''), true);

            if (!is_array($columns)) {
                continue;
            }

            $columns = array_map(
                static fn (mixed $column): mixed => $column === $from ? $to : $column,
                $columns,
            );

            db()->table('admin_table_preferences')
                ->where('id', $preference['id'])
                ->update(['visible_columns' => json_encode($columns, JSON_THROW_ON_ERROR)]);
        }
    }
};
