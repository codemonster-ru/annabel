<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $bio
 */
class Profile extends Model
{
    protected string $table = 'profiles';
    protected array $guarded = [];
    protected array $fillable = ['id', 'user_id', 'bio'];
}
