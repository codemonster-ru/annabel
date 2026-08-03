<?php

use Codemonster\Database\Seeders\Seeder;
use Codemonster\DateTime\DateTime;
use Psr\Clock\ClockInterface;

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

        $clock = app(ClockInterface::class);

        if (!$clock instanceof ClockInterface) {
            throw new RuntimeException('Clock service is not available.');
        }

        $now = DateTime::now($clock, 'UTC')->format(DateTime::DATABASE_FORMAT);

        db()->table('site_settings')->insert([
            'id' => 1,
            'site_name' => 'Annabel',
            'locale' => 'en',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
