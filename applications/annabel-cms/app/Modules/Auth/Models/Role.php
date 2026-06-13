<?php

namespace Codemonster\Cms\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;

/**
 * @property int|string $id
 * @property string $name
 */
class Role extends Model
{
    protected string $table = 'roles';

    /** @var list<string> */
    protected array $fillable = [
        'id',
        'name',
    ];

    public static function findByName(string $name): ?self
    {
        $role = static::query()
            ->where('name', $name)
            ->first();

        return $role instanceof self ? $role : null;
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
