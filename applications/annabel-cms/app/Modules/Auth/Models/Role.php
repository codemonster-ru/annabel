<?php

namespace Codemonster\Xen\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;

class Role extends Model
{
    protected string $table = 'roles';

    protected array $fillable = [
        'id',
        'name',
    ];

    public static function findByName(string $name): ?self
    {
        return static::query()
            ->where('name', $name)
            ->first();
    }

    public static function findOrCreate(string $name): self
    {
        $role = static::findByName($name);

        if ($role) {
            return $role;
        }

        return static::create(['name' => $name]);
    }
}
