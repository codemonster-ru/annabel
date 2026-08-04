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
        $groups = [
            ['name' => 'Admin', 'code' => 'admin', 'sort_order' => 1],
            ['name' => 'User', 'code' => 'user', 'sort_order' => 2],
        ];

        foreach ($groups as $group) {
            $exists = db()
                ->table('groups')
                ->where('code', $group['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            db()->table('groups')->insert([
                'name' => $group['name'],
                'code' => $group['code'],
                'sort_order' => $group['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
