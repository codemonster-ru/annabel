<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property int $user_id
 * @property User|null $author
 */
class Post extends Model
{
    protected string $table = 'posts';
    protected array $guarded = [];
    protected array $fillable = ['id', 'title', 'user_id'];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
