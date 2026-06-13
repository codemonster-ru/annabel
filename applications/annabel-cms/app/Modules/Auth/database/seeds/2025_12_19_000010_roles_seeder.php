<?php

use Codemonster\Database\Seeders\Seeder;

return new class () extends Seeder {
    public function run(): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $roles = ['user', 'admin'];

        foreach ($roles as $name) {
            $exists = db()
                ->table('roles')
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            db()->table('roles')->insert([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
