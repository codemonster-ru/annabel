<?php

use Codemonster\Database\Seeders\Seeder;
use Codemonster\DateTime\DateTime;
use Psr\Clock\ClockInterface;

return new class () extends Seeder {
    public function run(): void
    {
        $clock = app(ClockInterface::class);

        if (!$clock instanceof ClockInterface) {
            throw new RuntimeException('Clock service is not available.');
        }

        $now = DateTime::now($clock, 'UTC')->format(DateTime::DATABASE_FORMAT);
        $roles = [
            ['name' => 'Admin', 'code' => 'admin', 'sort_order' => 1],
            ['name' => 'User', 'code' => 'user', 'sort_order' => 2],
        ];

        foreach ($roles as $role) {
            $exists = db()
                ->table('roles')
                ->where('code', $role['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            db()->table('roles')->insert([
                'name' => $role['name'],
                'code' => $role['code'],
                'sort_order' => $role['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
