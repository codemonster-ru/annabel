<?php

namespace Codemonster\Cms\Modules\Pages\Models;

use Codemonster\Database\ORM\Model;

/**
 * @property int|string $id
 * @property string $slug
 * @property string $title
 * @property string $content
 * @property bool $is_published
 * @property string|null $published_at
 */
class Page extends Model
{
    protected string $table = 'pages';

    /** @var list<string> */
    protected array $fillable = [
        'id',
        'slug',
        'title',
        'content',
        'is_published',
        'published_at',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function findPublishedBySlug(string $slug): ?self
    {
        $page = static::query()
            ->where('slug', self::normalizeSlug($slug))
            ->where('is_published', 1)
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
}
