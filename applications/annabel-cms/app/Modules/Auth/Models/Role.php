<?php

namespace Codemonster\Cms\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelQuery;
use Codemonster\Database\Query\QueryBuilder;

/**
 * @property int|string $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property bool $is_active
 * @property \DateTimeImmutable|null $active_from
 * @property \DateTimeImmutable|null $active_until
 * @property int $sort_order
 * @property \DateTimeImmutable|null $created_at
 * @property \DateTimeImmutable|null $updated_at
 */
class Role extends Model
{
    protected string $table = 'roles';

    /** @var list<string> */
    protected array $fillable = ['id', 'name', 'code', 'description', 'is_active', 'active_from', 'active_until', 'sort_order'];

    /** @var array<string, string> */
    protected array $casts = [
        'is_active' => 'boolean',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function isActiveAt(?\DateTimeInterface $at = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $timestamp = ($at ?? new \DateTimeImmutable())->getTimestamp();

        return ($this->active_from === null || $this->active_from->getTimestamp() <= $timestamp)
            && ($this->active_until === null || $this->active_until->getTimestamp() >= $timestamp);
    }

    /** @return ModelQuery<static> */
    public static function activeQueryAt(?\DateTimeInterface $at = null): ModelQuery
    {
        $date = \DateTimeImmutable::createFromInterface(
            $at ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        )
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');

        return static::query()
            ->where('roles.is_active', 1)
            ->where(static function (QueryBuilder $query) use ($date): void {
                $query->whereNull('roles.active_from')
                    ->orWhere('roles.active_from', '<=', $date);
            })
            ->where(static function (QueryBuilder $query) use ($date): void {
                $query->whereNull('roles.active_until')
                    ->orWhere('roles.active_until', '>=', $date);
            });
    }

    public static function findByCode(string $code): ?self
    {
        $role = static::query()->where('code', $code)->first();

        return $role instanceof self ? $role : null;
    }
}
