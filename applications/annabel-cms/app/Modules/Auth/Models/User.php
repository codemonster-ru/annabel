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
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id');
    }

    /**
     * @return array<int, string>
     */
    public function groupCodes(): array
    {
        $query = Group::activeQueryAt();
        $query->getBuilder()
            ->join('group_user', 'groups.id', '=', 'group_user.group_id')
            ->select('groups.*');
        $this->applyActiveMembershipWindow($query);
        $groups = $query
            ->where('group_user.user_id', $this->id)
            ->get();

        $codes = [];

        foreach ($groups as $group) {
            $code = $group->code;

            if (is_string($code) && $code !== '') {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    public function hasGroup(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        $query = Group::activeQueryAt();
        $query->getBuilder()->join('group_user', 'groups.id', '=', 'group_user.group_id');
        $this->applyActiveMembershipWindow($query);

        return $query
            ->where('group_user.user_id', $this->id)
            ->where('groups.code', $code)
            ->exists();
    }

    public function assignGroup(string $code): void
    {
        if ($code === '') {
            return;
        }

        $group = Group::findByCode($code);

        if (!$group instanceof Group) {
            throw new \RuntimeException('Group not found.');
        }

        $groupId = $group->id ?? null;

        if (!$groupId) {
            throw new \RuntimeException('Group not found or missing id.');
        }

        $exists = db()
            ->table('group_user')
            ->where('user_id', $this->id)
            ->where('group_id', $groupId)
            ->exists();

        if (!$exists) {
            db()->table('group_user')->insert([
                'user_id' => $this->id,
                'group_id' => $groupId,
            ]);
        }
    }

    /** @param \Codemonster\Database\ORM\ModelQuery<Group> $query */
    private function applyActiveMembershipWindow(\Codemonster\Database\ORM\ModelQuery $query, ?\DateTimeInterface $at = null): void
    {
        $date = \DateTimeImmutable::createFromInterface(
            $at ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        )
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');

        $query
            ->where(static function (\Codemonster\Database\Query\QueryBuilder $builder) use ($date): void {
                $builder->whereNull('group_user.active_from')
                    ->orWhere('group_user.active_from', '<=', $date);
            })
            ->where(static function (\Codemonster\Database\Query\QueryBuilder $builder) use ($date): void {
                $builder->whereNull('group_user.active_until')
                    ->orWhere('group_user.active_until', '>=', $date);
            });
    }
}
