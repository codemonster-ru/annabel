<?php

namespace Codemonster\Cms\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;

/**
 * @property int|string $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property \DateTimeImmutable|null $created_at
 * @property \DateTimeImmutable|null $updated_at
 */
class Group extends Model
{
    protected string $table = 'groups';

    /** @var list<string> */
    protected array $fillable = ['id', 'name', 'code', 'description', 'is_active', 'sort_order'];

    /** @var array<string, string> */
    protected array $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function findByName(string $name): ?self
    {
        $group = static::query()->where('name', $name)->first();

        return $group instanceof self ? $group : null;
    }

    public static function findByCode(string $code): ?self
    {
        $group = static::query()->where('code', $code)->first();

        return $group instanceof self ? $group : null;
    }

    public static function findOrCreate(string $name): self
    {
        $group = static::findByName($name);

        return $group ?? static::create(['name' => $name]);
    }
}
