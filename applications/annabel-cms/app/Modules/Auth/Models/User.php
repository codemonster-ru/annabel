<?php

namespace Codemonster\Cms\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Relations\BelongsToMany;

/**
 * @property int|string $id
 * @property string $email
 * @property string $password
 */
class User extends Model
{
    protected string $table = 'users';

    /** @var list<string> */
    protected array $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];

    /** @var list<string> */
    protected array $hidden = [
        'password',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function findByEmail(string $email): ?self
    {
        $user = static::query()
            ->where('email', $email)
            ->first();

        return $user instanceof self ? $user : null;
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    /**
     * @return array<int, string>
     */
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
            $name = $role['name'] ?? null;

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
