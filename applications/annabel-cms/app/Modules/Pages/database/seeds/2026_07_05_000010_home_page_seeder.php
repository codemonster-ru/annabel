<?php

use Codemonster\Database\Seeders\Seeder;
use Codemonster\DateTime\DateTime;
use Psr\Clock\ClockInterface;

return new class () extends Seeder {
    public function run(): void
    {
        $exists = db()
            ->table('pages')
            ->where('slug', 'home')
            ->exists();

        if ($exists) {
            return;
        }

        $clock = app(ClockInterface::class);

        if (!$clock instanceof ClockInterface) {
            throw new RuntimeException('Clock service is not available.');
        }

        $now = DateTime::now($clock, 'UTC')->format(DateTime::DATABASE_FORMAT);

        db()->table('pages')->insert([
            'slug' => 'home',
            'title' => 'Welcome to Annabel',
            'content' => 'Your CMS is installed and ready for content.',
            'is_active' => 1,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
