<?php

use Codemonster\Database\Seeders\Seeder;

return new class () extends Seeder {
    public function run(): void
    {
        $exists = db()
            ->table('site_settings')
            ->where('id', 1)
            ->exists();

        if ($exists) {
            return;
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        db()->table('site_settings')->insert([
            'id' => 1,
            'site_name' => 'Annabel',
            'locale' => 'en',
            'timezone' => 'UTC',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
