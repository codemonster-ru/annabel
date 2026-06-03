<?php

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;

class Profile extends Model
{
    protected string $table = 'profiles';
    protected array $guarded = [];
    protected array $fillable = ['id', 'user_id', 'bio'];
}
