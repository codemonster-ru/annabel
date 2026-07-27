<?php

namespace Codemonster\Cms\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Relations\BelongsToMany;

/**
 * @property int|string $id
 * @property string $username
 * @property string $email
 * @property bool $is_active
 * @property string $password
 * @property string|null $remember_token
 * @property \DateTimeImmutable|null $created_at
 * @property \DateTimeImmutable|null $updated_at
 */
class User extends Model
{
    protected string $table = 'users';

    /** @var list<string> */
    protected array $fillable = [
        'id',
        'username',
        'email',
        'is_active',
        'password',
        'remember_token',
    ];

    /** @var list<string> */
    protected array $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function findByEmail(string $email): ?self
    {
        $user = static::query()
            ->where('email', $email)
            ->first();

        return $user instanceof self ? $user : null;
    }

    public static function findByUsername(string $username): ?self
    {
        $user = static::query()
            ->where('username', $username)
            ->first();

        return $user instanceof self ? $user : null;
    }

    public static function findByLogin(string $login): ?self
    {
        return str_contains($login, '@')
            ? static::findByEmail($login)
            : static::findByUsername($login);
    }

    public static function validUsername(string $username): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{2,59}$/', $username) === 1;
    }

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id');
    }

    /**
     * @return array<int, string>
     */
    public function groupNames(): array
    {
        $groups = db()
            ->table('group_user')
            ->join('groups', 'group_user.group_id', '=', 'groups.id')
            ->where('user_id', $this->id)
            ->select('groups.*')
            ->get();

        $names = [];

        foreach ($groups as $group) {
            $name = $group['name'] ?? null;

            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function hasGroup(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return db()
            ->table('group_user')
            ->join('groups', 'group_user.group_id', '=', 'groups.id')
            ->where('user_id', $this->id)
            ->where('name', $name)
            ->exists();
    }

    public function assignGroup(string $name): void
    {
        if ($name === '') {
            return;
        }

        $role = Group::findOrCreate($name);
        $roleId = $role->id ?? null;

        if (!$roleId) {
            throw new \RuntimeException('Group not found or missing id.');
        }

        $exists = db()
            ->table('group_user')
            ->where('user_id', $this->id)
            ->where('group_id', $roleId)
            ->exists();

        if (!$exists) {
            db()->table('group_user')->insert([
                'user_id' => $this->id,
                'group_id' => $roleId,
            ]);
        }
    }
}
