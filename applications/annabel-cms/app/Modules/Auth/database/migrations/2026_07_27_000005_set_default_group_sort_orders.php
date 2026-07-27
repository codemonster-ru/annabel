<?php

use Codemonster\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        db()->table('groups')->where('name', 'admin')->update(['sort_order' => 0]);
        db()->table('groups')->where('name', 'user')->update(['sort_order' => 100]);
    }

    public function down(): void
    {
        db()->table('groups')->whereIn('name', ['admin', 'user'])->update(['sort_order' => 0]);
    }
};
