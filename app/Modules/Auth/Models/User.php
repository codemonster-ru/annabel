<?php

namespace Codemonster\Xen\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Relations\BelongsToMany;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];

    protected array $hidden = [
        'password',
    ];

    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function findByEmail(string $email): ?self
    {
        return static::query()
            ->where('email', $email)
            ->first();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function roleNames(): array
    {
        $roles = db()
            ->table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('user_id', $this->id)
            ->select('roles.*')
            ->get();

        $names = [];

        foreach ($roles as $role) {
            $name = is_array($role)
                ? ($role['name'] ?? null)
                : ($role->name ?? null);

            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function hasRole(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return db()
            ->table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('user_id', $this->id)
            ->where('name', $name)
            ->exists();
    }

    public function assignRole(string $name): void
    {
        if ($name === '') {
            return;
        }

        $role = Role::findOrCreate($name);
        $roleId = $role->id ?? null;

        if (!$roleId) {
            throw new \RuntimeException('Role not found or missing id.');
        }

        $exists = db()
            ->table('role_user')
            ->where('user_id', $this->id)
            ->where('role_id', $roleId)
            ->exists();

        if (!$exists) {
            db()->table('role_user')->insert([
                'user_id' => $this->id,
                'role_id' => $roleId,
            ]);
        }
    }
}
