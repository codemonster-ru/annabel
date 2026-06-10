<?php

namespace App\Models;

use Codemonster\Database\ORM\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    protected array $hidden = [
        'password',
        'remember_token',
    ];

    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];
}
