<?php

namespace Codemonster\Cms\Modules\Pages\Models;

use Codemonster\Database\ORM\Model;

/**
 * @property int|string $id
 * @property int|string|null $created_by
 * @property int|string|null $owner_id
 * @property int|string|null $updated_by
 * @property string $slug
 * @property string $title
 * @property int $sort_order
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string $content
 * @property bool $is_active
 * @property \DateTimeInterface|null $created_at
 * @property \DateTimeInterface|null $activated_at
 * @property \DateTimeInterface|null $active_from
 * @property \DateTimeInterface|null $active_until
 * @property \DateTimeInterface|null $updated_at
 */
class Page extends Model
{
    protected string $table = 'pages';

    /** @var list<string> */
    protected array $fillable = [
        'id',
        'created_by',
        'owner_id',
        'updated_by',
        'slug',
        'title',
        'sort_order',
        'meta_title',
        'meta_description',
        'content',
        'is_active',
        'activated_at',
        'active_from',
        'active_until',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'created_by' => 'integer',
        'owner_id' => 'integer',
        'updated_by' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'activated_at' => 'datetime',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function findActiveBySlug(string $slug): ?self
    {
        $page = static::query()
            ->where('slug', self::normalizeSlug($slug))
            ->where('is_active', 1)
            ->where(static function ($query): void {
                $query->whereNull('active_from')
                    ->orWhere('active_from', '<=', gmdate('Y-m-d H:i:s'));
            })
            ->where(static function ($query): void {
                $query->whereNull('active_until')
                    ->orWhere('active_until', '>=', gmdate('Y-m-d H:i:s'));
            })
            ->first();

        return $page instanceof self ? $page : null;
    }

    public static function validSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $slug) === 1;
    }

    public static function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    public function isOwnedBy(int|string $userId): bool
    {
        return $this->owner_id !== null
            && (string) $this->owner_id === (string) $userId;
    }
}
