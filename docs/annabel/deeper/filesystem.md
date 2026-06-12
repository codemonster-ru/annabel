---
title: "File storage"
description: "Filesystem disks and local storage"
order: 4
---

# File storage

Annabel exposes filesystem disks through `storage()`.

## Usage

Resolve a configured disk and perform operations relative to its root.

```php
storage('public')->put('avatars/user-1.txt', 'avatar');

$contents = storage('public')->get('avatars/user-1.txt');
```

Use the default disk by omitting the disk name:

```php
storage()->disk()->put('reports/monthly.txt', 'Report');
```

## Configuration

Configure disks in `config/filesystem.php`. The local filesystem protects its
configured root and rejects paths that escape it.

```php
return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => base_path('storage/app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => base_path('storage/app/public'),
            'url' => env('APP_URL', 'http://localhost') . '/storage',
        ],
    ],
];
```

The currently supported filesystem driver is `local`.

## File operations

The filesystem API supports reading, writing, copying, moving, and deleting
files.

```php
$disk = storage('public');

$disk->exists('avatars/user-1.txt');
$disk->missing('avatars/user-1.txt');
$disk->append('logs.txt', "line\n");
$disk->prepend('logs.txt', "start\n");
$disk->copy('from.txt', 'to.txt');
$disk->move('old.txt', 'new.txt');
$disk->delete('old.txt');
```

## Metadata

Inspect file metadata without exposing the disk's underlying storage path.

```php
$size = storage('public')->size('avatars/user-1.txt');
$modified = storage('public')->lastModified('avatars/user-1.txt');
$mime = storage('public')->mimeType('avatars/user-1.txt');
$url = storage('public')->url('avatars/user-1.txt');
$path = storage('public')->path('avatars/user-1.txt');
```

## Directories

Create, inspect, and remove directories through the same disk abstraction.

```php
storage('local')->makeDirectory('exports');
storage('local')->deleteDirectory('exports');

$files = storage('local')->files('exports');
$directories = storage('local')->directories();
```
