<?php

use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        schema()->table('groups', static function (Blueprint $table): void {
            $table->string('code')->nullable()->unique();
        });

        db()->table('groups')->where('name', 'admin')->update(['code' => 'admin']);
        db()->table('groups')->where('name', 'user')->update(['code' => 'user']);

        foreach (db()->table('groups')->get() as $group) {
            if (($group['code'] ?? null) === null) {
                db()->table('groups')->where('id', $group['id'])->update([
                    'code' => 'group_' . $group['id'],
                ]);
            }
        }
    }

    public function down(): void
    {
        schema()->table('groups', static function (Blueprint $table): void {
            $table->dropColumn('code');
        });
    }
};
