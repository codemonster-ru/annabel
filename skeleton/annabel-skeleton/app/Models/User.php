<?php

namespace App\Models;

use Codemonster\Database\ORM\Model;

class User extends Model
{
    protected string $table = 'users';

    /**
     * @var list<string>
     */
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected array $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];
}
