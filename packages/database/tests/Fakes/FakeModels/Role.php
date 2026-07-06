<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property ModelCollection<User> $users
 */
class Role extends Model
{
    protected string $table = 'roles';
    protected array $guarded = [];
    protected array $fillable = ['id', 'name'];

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
