<?php

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;

class Role extends Model
{
    protected string $table = 'roles';
    protected array $guarded = [];
    protected array $fillable = ['id', 'name'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
