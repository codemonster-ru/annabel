# codemonster-ru/filesystem

Filesystem storage primitives for Annabel applications.

## Installation

```bash
composer require codemonster-ru/filesystem
```

## Usage

```php
use Codemonster\Filesystem\FilesystemManager;

$storage = new FilesystemManager([
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => __DIR__ . '/storage/app',
        ],
    ],
]);

$disk = $storage->disk();
$disk->put('reports/monthly.txt', 'Report');

echo $disk->get('reports/monthly.txt');
```

`LocalFilesystem` protects its configured root and rejects paths that escape it.

## Annabel integration

`codemonster-ru/annabel` registers `FilesystemManager`,
`FilesystemInterface`, and the `storage()` helper through
`FilesystemServiceProvider`.
