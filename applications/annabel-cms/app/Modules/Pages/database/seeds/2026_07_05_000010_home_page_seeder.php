<?php

use Codemonster\Database\Seeders\Seeder;

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

        $now = date('Y-m-d H:i:s');

        db()->table('pages')->insert([
            'slug' => 'home',
            'title' => 'Welcome to Annabel CMS',
            'content' => 'Your CMS is installed and ready for content.',
            'is_published' => 1,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
