<?php

use Codemonster\Filesystem\Contracts\FilesystemInterface;
use Codemonster\Filesystem\FilesystemManager;

if (!function_exists('storage')) {
    function storage(?string $disk = null): FilesystemManager|FilesystemInterface
    {
        /** @var FilesystemManager $manager */
        $manager = app(FilesystemManager::class);

        return $disk === null ? $manager : $manager->disk($disk);
    }
}
