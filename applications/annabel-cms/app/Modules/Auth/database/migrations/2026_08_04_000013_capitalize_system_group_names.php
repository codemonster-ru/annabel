<?php

use Codemonster\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        db()->table('groups')->where('code', 'admin')->update(['name' => 'Admin']);
        db()->table('groups')->where('code', 'user')->update(['name' => 'User']);
    }

    public function down(): void
    {
        db()->table('groups')->where('code', 'admin')->update(['name' => 'admin']);
        db()->table('groups')->where('code', 'user')->update(['name' => 'user']);
    }
};
