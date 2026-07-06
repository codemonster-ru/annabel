<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Relations\BelongsTo;
use Codemonster\Database\Relations\BelongsToMany;
use Codemonster\Database\Relations\HasMany;
use Codemonster\Database\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $deleted_at
 * @property ModelCollection<Post> $posts
 * @property Profile|null $profile
 * @property ModelCollection<Role> $roles
 */
class User extends Model
{
    protected string $table = 'users';
    protected array $guarded = [];
    protected array $fillable = ['id', 'name', 'email', 'deleted_at'];

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return HasOne<Profile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
