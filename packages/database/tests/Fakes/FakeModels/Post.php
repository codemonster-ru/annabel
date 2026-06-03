<?php

namespace Codemonster\Database\Tests\Fakes\FakeModels;

use Codemonster\Database\ORM\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $guarded = [];
    protected array $fillable = ['id', 'title', 'user_id'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
