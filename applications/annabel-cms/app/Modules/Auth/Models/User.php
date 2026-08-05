<?php

namespace Codemonster\Cms\Modules\Auth\Models;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Relations\BelongsToMany;

/**
 * @property int|string $id
 * @property string $username
 * @property string $email
 * @property bool $is_active
 * @property \DateTimeImmutable|null $active_from
 * @property \DateTimeImmutable|null $active_until
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
        'active_from',
        'active_until',
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
        'active_from' => 'datetime',
        'active_until' => 'datetime',
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

    public function isActiveAt(?\DateTimeInterface $at = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $timestamp = ($at ?? new \DateTimeImmutable())->getTimestamp();

        return ($this->active_from === null || $this->active_from->getTimestamp() <= $timestamp)
            && ($this->active_until === null || $this->active_until->getTimestamp() >= $timestamp);
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
    public function roleCodes(): array
    {
        $query = Role::activeQueryAt();
        $query->getBuilder()
            ->join('role_user', 'roles.id', '=', 'role_user.role_id')
            ->select('roles.*');
        $this->applyActiveMembershipWindow($query);
        $roles = $query
            ->where('role_user.user_id', $this->id)
            ->get();

        $codes = [];

        foreach ($roles as $role) {
            $code = $role->code;

            if (is_string($code) && $code !== '') {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    public function hasRole(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        $query = Role::activeQueryAt();
        $query->getBuilder()->join('role_user', 'roles.id', '=', 'role_user.role_id');
        $this->applyActiveMembershipWindow($query);

        return $query
            ->where('role_user.user_id', $this->id)
            ->where('roles.code', $code)
            ->exists();
    }

    /** @return array<int, string> */
    public function permissionCodes(): array
    {
        $query = Role::query();
        $query->getBuilder()
            ->join('role_permission', 'roles.id', '=', 'role_permission.role_id')
            ->join('role_user', 'roles.id', '=', 'role_user.role_id')
            ->select('role_permission.permission');
        $this->applyActiveRoleWindow($query);
        $this->applyActiveMembershipWindow($query);

        $assignments = $query
            ->where('role_user.user_id', $this->id)
            ->get();
        $codes = [];

        foreach ($assignments as $assignment) {
            $code = $assignment->getAttribute('permission');

            if (is_string($code) && $code !== '') {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }

    public function hasPermission(string $permission): bool
    {
        return $permission !== '' && in_array($permission, $this->permissionCodes(), true);
    }

    public function assignRole(string $code): void
    {
        if ($code === '') {
            return;
        }

        $role = Role::findByCode($code);

        if (!$role instanceof Role) {
            throw new \RuntimeException('Role not found.');
        }

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

    /**
     * @template TModel of \Codemonster\Database\ORM\Model
     * @param \Codemonster\Database\ORM\ModelQuery<TModel> $query
     */
    private function applyActiveMembershipWindow(\Codemonster\Database\ORM\ModelQuery $query, ?\DateTimeInterface $at = null): void
    {
        $date = \DateTimeImmutable::createFromInterface(
            $at ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        )
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');

        $query
            ->where(static function (\Codemonster\Database\Query\QueryBuilder $builder) use ($date): void {
                $builder->whereNull('role_user.active_from')
                    ->orWhere('role_user.active_from', '<=', $date);
            })
            ->where(static function (\Codemonster\Database\Query\QueryBuilder $builder) use ($date): void {
                $builder->whereNull('role_user.active_until')
                    ->orWhere('role_user.active_until', '>=', $date);
            });
    }

    /** @param \Codemonster\Database\ORM\ModelQuery<Role> $query */
    private function applyActiveRoleWindow(\Codemonster\Database\ORM\ModelQuery $query, ?\DateTimeInterface $at = null): void
    {
        $date = \DateTimeImmutable::createFromInterface(
            $at ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        )
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');

        $query
            ->where('roles.is_active', 1)
            ->where(static function (\Codemonster\Database\Query\QueryBuilder $builder) use ($date): void {
                $builder->whereNull('roles.active_from')
                    ->orWhere('roles.active_from', '<=', $date);
            })
            ->where(static function (\Codemonster\Database\Query\QueryBuilder $builder) use ($date): void {
                $builder->whereNull('roles.active_until')
                    ->orWhere('roles.active_until', '>=', $date);
            });
    }
}
