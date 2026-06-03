<?php

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $guarded = [];
    protected array $fillable = ['id', 'name', 'email', 'deleted_at'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
