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
            'admin' => 1,
            'user' => 2,
        ];

        foreach ($groups as $name => $sortOrder) {
            $exists = db()
                ->table('groups')
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            db()->table('groups')->insert([
                'name' => $name,
                'sort_order' => $sortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
