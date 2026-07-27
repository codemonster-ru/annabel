<?php

use Codemonster\Database\Seeders\Seeder;

return new class () extends Seeder {
    public function run(): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
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
