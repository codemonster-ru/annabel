<?php

namespace Codemonster\Cms\Modules\AdminUsers\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\Role;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\InvalidDateTimeException;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;
use Psr\Clock\ClockInterface;

class UserListController
{
    private const TABLE_KEY = 'users';

    /** @var list<string> */
    private const ALL_COLUMNS = ['actions', 'id', 'username', 'email', 'is_active', 'created_at', 'updated_at'];

    /** @var list<string> */
    private const DEFAULT_COLUMNS = ['actions', 'id', 'username', 'email', 'is_active', 'created_at', 'updated_at'];

    private const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        private AdminScreenRendererInterface $admin,
        private UserSessionInterface $users,
        private Validator $validator,
        private ClockInterface $clock,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.users.list', 'admin.users.list', 'Users');
    }

    public function create(): Response
    {
        return $this->admin->renderAuthenticated('admin.users.list', 'admin.users.list', 'New user');
    }

    public function edit(string $id): Response
    {
        if (!User::find($id) instanceof User) {
            abort(404);
        }

        return $this->admin->renderAuthenticated('admin.users.list', 'admin.users.list', 'Edit user');
    }

    public function data(Request $request): Response
    {
        $query = User::query();
        $query->getBuilder()->orderBy('id', 'desc');
        $page = $this->positiveInteger($request->query('page'), 1);
        $perPage = $this->positiveInteger($request->query('per_page'), self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $pagination = $query->paginate($perPage, $page);
        $users = [];

        foreach ($pagination['data'] as $user) {
            $users[] = $this->payload($user);
        }

        return Response::json([
            'data' => $users,
            'total' => $pagination['total'],
            'current_page' => $pagination['current_page'],
            'per_page' => $pagination['per_page'],
            'csrf_token' => csrf_token(),
            'visible_columns' => $this->visibleColumns(),
        ]);
    }

    public function updatePreferences(Request $request): Response
    {
        $user = $this->users->current();

        if ($user === null) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $value = $request->input('visible_columns');
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        $columns = is_array($value) ? $value : [];
        $columns = array_values(array_unique(array_filter(
            $columns,
            static fn (mixed $column): bool => is_string($column) && in_array($column, self::ALL_COLUMNS, true),
        )));

        if (!in_array('actions', $columns, true)) {
            array_unshift($columns, 'actions');
        }

        $now = DateTime::now($this->clock, 'UTC')->format(DateTime::DATABASE_FORMAT);
        $preferences = db()->table('admin_table_preferences')
            ->where('user_id', $user->id)
            ->where('table_key', self::TABLE_KEY)
            ->first();

        if ($preferences) {
            db()->table('admin_table_preferences')->where('id', $preferences['id'])->update([
                'visible_columns' => json_encode($columns, JSON_THROW_ON_ERROR),
                'updated_at' => $now,
            ]);
        } else {
            db()->table('admin_table_preferences')->insert([
                'user_id' => $user->id,
                'table_key' => self::TABLE_KEY,
                'visible_columns' => json_encode($columns, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return Response::json(['visible_columns' => $columns]);
    }

    public function showData(string $id): Response
    {
        $user = User::find($id);

        if (!$user instanceof User) {
            return Response::json(['message' => 'User not found.'], 404);
        }

        return Response::json([
            'user' => $this->payload($user),
            'roles' => $this->roleOptionPayload($user),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function roleOptions(): Response
    {
        return Response::json([
            'roles' => $this->roleOptionPayload(),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, new User());
    }

    public function update(Request $request, string $id): Response
    {
        $user = User::find($id);

        if (!$user instanceof User) {
            return Response::json(['message' => 'User not found.'], 404);
        }

        return $this->save($request, $user);
    }

    public function destroy(string $id): Response
    {
        $user = User::find($id);
        $current = $this->users->current();

        if (!$user instanceof User) {
            return Response::json(['message' => 'User not found.'], 404);
        }

        if ($current !== null && (string) $current->id === (string) $user->id) {
            return Response::json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return Response::json(['message' => 'User deleted successfully.']);
    }

    private function save(Request $request, User $user): Response
    {
        $password = (string) $request->input('password');
        $validated = $this->validator->validateOrFail([
            'username' => trim((string) $request->input('username')),
            'email' => trim((string) $request->input('email')),
            'is_active' => $request->input('is_active'),
            'active_from' => trim((string) $request->input('active_from')),
            'active_until' => trim((string) $request->input('active_until')),
            'password' => $password,
            'password_confirmation' => (string) $request->input('password_confirmation'),
        ], [
            'username' => 'required|string|min:3|max:60',
            'email' => 'required|email|max:255',
            'is_active' => 'nullable|boolean',
            'active_from' => 'nullable|string',
            'active_until' => 'nullable|string',
            'password' => $user->id ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string',
        ]);

        $activeFrom = $this->toUtc($validated['active_from']);
        $activeUntil = $this->toUtc($validated['active_until']);

        if ($validated['active_from'] !== '' && $activeFrom === null) {
            return $this->invalid('active_from', 'The activity start must be a valid date and time.');
        }
        if ($validated['active_until'] !== '' && $activeUntil === null) {
            return $this->invalid('active_until', 'The activity end must be a valid date and time.');
        }
        if ($activeFrom !== null && $activeUntil !== null && $activeUntil <= $activeFrom) {
            return $this->invalid('active_until', 'The activity end must be after the activity start.');
        }

        $roleAssignments = $this->validatedRoleAssignments($request);
        if ($roleAssignments instanceof Response) {
            return $roleAssignments;
        }

        if (!User::validUsername($validated['username'])) {
            return $this->invalid('username', 'Username may contain only letters, numbers, underscores, or hyphens and must start with a letter or number.');
        }

        $duplicateUsername = User::query()
            ->where('username', $validated['username'])
            ->where('id', '!=', $user->id ?? 0)
            ->first();
        if ($duplicateUsername instanceof User) {
            return $this->invalid('username', 'This username is already in use.');
        }

        $duplicateEmail = User::query()
            ->where('email', $validated['email'])
            ->where('id', '!=', $user->id ?? 0)
            ->first();
        if ($duplicateEmail instanceof User) {
            return $this->invalid('email', 'This email is already in use.');
        }

        transaction(function () use ($request, $validated, $activeFrom, $activeUntil, $password, $user, $roleAssignments): void {
            $user->fill([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'is_active' => in_array((string) $request->input('is_active'), ['1', 'true', 'on'], true),
                'active_from' => $activeFrom,
                'active_until' => $activeUntil,
            ]);

            if ($password !== '') {
                $user->password = password_hash($password, PASSWORD_DEFAULT);
            }

            $user->save();
            $this->syncRoleAssignments($user, $roleAssignments);
        });

        return Response::json([
            'message' => 'User saved successfully.',
            'user' => $this->payload($user),
            'roles' => $this->roleOptionPayload($user),
        ]);
    }

    /**
     * @return list<array{role_id: int, active_from: string|null, active_until: string|null}>|Response
     */
    private function validatedRoleAssignments(Request $request): array|Response
    {
        $value = $request->input('roles');

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return $this->invalid('roles', 'The selected roles must be a valid list.');
        }

        $assignments = [];
        $roleIds = [];

        foreach ($value as $membership) {
            if (!is_array($membership)) {
                return $this->invalid('roles', 'The selected roles must be a valid list.');
            }

            $id = $membership['id'] ?? null;
            if ((!is_int($id) && !is_string($id)) || preg_match('/\A[1-9]\d*\z/', (string) $id) !== 1) {
                return $this->invalid('roles', 'A selected role is invalid.');
            }

            $roleId = (int) $id;
            if (isset($roleIds[$roleId])) {
                return $this->invalid('roles', 'A role may only be selected once.');
            }

            $activeFromValue = trim(is_string($membership['active_from'] ?? null) ? $membership['active_from'] : '');
            $activeUntilValue = trim(is_string($membership['active_until'] ?? null) ? $membership['active_until'] : '');
            $membershipActiveFrom = $this->toUtc($activeFromValue);
            $membershipActiveUntil = $this->toUtc($activeUntilValue);

            if ($activeFromValue !== '' && $membershipActiveFrom === null) {
                return $this->invalid("roles.{$roleId}.active_from", 'The membership start must be a valid date and time.');
            }
            if ($activeUntilValue !== '' && $membershipActiveUntil === null) {
                return $this->invalid("roles.{$roleId}.active_until", 'The membership end must be a valid date and time.');
            }
            if ($membershipActiveFrom !== null && $membershipActiveUntil !== null && $membershipActiveUntil <= $membershipActiveFrom) {
                return $this->invalid("roles.{$roleId}.active_until", 'The membership end must be after the membership start.');
            }

            $roleIds[$roleId] = true;
            $assignments[] = [
                'role_id' => $roleId,
                'active_from' => $membershipActiveFrom,
                'active_until' => $membershipActiveUntil,
            ];
        }

        if ($roleIds !== []) {
            $knownIds = [];
            foreach (Role::query()->whereIn('id', array_keys($roleIds))->get() as $role) {
                $knownIds[(int) $role->id] = true;
            }

            if (count($knownIds) !== count($roleIds)) {
                return $this->invalid('roles', 'A selected role does not exist.');
            }
        }

        return $assignments;
    }

    /** @param list<array{role_id: int, active_from: string|null, active_until: string|null}> $assignments */
    private function syncRoleAssignments(User $user, array $assignments): void
    {
        $desired = [];
        foreach ($assignments as $assignment) {
            $desired[$assignment['role_id']] = $assignment;
        }

        foreach (db()->table('role_user')->where('user_id', $user->id)->get() as $membership) {
            $roleId = (int) $membership['role_id'];

            if (!isset($desired[$roleId])) {
                db()->table('role_user')
                    ->where('user_id', $user->id)
                    ->where('role_id', $roleId)
                    ->delete();
                continue;
            }

            db()->table('role_user')
                ->where('user_id', $user->id)
                ->where('role_id', $roleId)
                ->update([
                    'active_from' => $desired[$roleId]['active_from'],
                    'active_until' => $desired[$roleId]['active_until'],
                ]);
            unset($desired[$roleId]);
        }

        foreach ($desired as $assignment) {
            db()->table('role_user')->insert(['user_id' => $user->id] + $assignment);
        }
    }

    /** @return list<array<string, mixed>> */
    private function roleOptionPayload(?User $user = null): array
    {
        $memberships = [];
        if ($user !== null) {
            foreach (db()->table('role_user')->where('user_id', $user->id)->get() as $membership) {
                $memberships[(int) $membership['role_id']] = $membership;
            }
        }

        $roles = Role::query();
        $roles->getBuilder()->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
        $payload = [];

        foreach ($roles->get() as $role) {
            $membership = $memberships[(int) $role->id] ?? null;
            $payload[] = [
                'id' => $role->id,
                'name' => (string) $role->name,
                'code' => (string) $role->code,
                'is_active' => (bool) $role->is_active,
                'selected' => $membership !== null,
                'active_from' => $this->toIso8601($membership['active_from'] ?? null),
                'active_until' => $this->toIso8601($membership['active_until'] ?? null),
            ];
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => (string) $user->username,
            'email' => (string) $user->email,
            'is_active' => (bool) $user->is_active,
            'active_from' => $this->toIso8601($user->active_from),
            'active_until' => $this->toIso8601($user->active_until),
            'created_at' => $user->created_at?->format(DATE_ATOM),
            'updated_at' => $user->updated_at?->format(DATE_ATOM),
        ];
    }

    private function toUtc(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z\z/', $value) !== 1) {
            return null;
        }

        try {
            $date = DateTime::parse($value);
        } catch (InvalidDateTimeException) {
            return null;
        }

        return $date->toTimezone('UTC')->format(DateTime::DATABASE_FORMAT);
    }

    private function toIso8601(mixed $value): string
    {
        if (is_string($value) && $value !== '') {
            try {
                return DateTime::parse($value, 'UTC')->format(DATE_ATOM);
            } catch (InvalidDateTimeException) {
                return '';
            }
        }

        if (!$value instanceof \DateTimeInterface) {
            return '';
        }

        return DateTime::fromInterface($value)
            ->toTimezone('UTC')
            ->format(DATE_ATOM);
    }

    private function invalid(string $field, string $message): Response
    {
        return Response::json([
            'message' => 'The given data was invalid.',
            'errors' => [$field => [$message]],
        ], 422);
    }

    private function positiveInteger(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return min(1_000_000, max(1, $value));
        }

        if (!is_string($value) || preg_match('/\A\d+\z/', $value) !== 1) {
            return $default;
        }

        return min(1_000_000, max(1, (int) $value));
    }

    /** @return list<string> */
    private function visibleColumns(): array
    {
        $user = $this->users->current();

        if ($user === null) {
            return self::DEFAULT_COLUMNS;
        }

        $preferences = db()->table('admin_table_preferences')
            ->where('user_id', $user->id)
            ->where('table_key', self::TABLE_KEY)
            ->first();

        if (!$preferences || !is_string($preferences['visible_columns'] ?? null)) {
            return self::DEFAULT_COLUMNS;
        }

        $columns = json_decode($preferences['visible_columns'], true);
        if (!is_array($columns)) {
            return self::DEFAULT_COLUMNS;
        }

        $columns = array_values(array_unique(array_filter(
            $columns,
            static fn (mixed $column): bool => is_string($column) && in_array($column, self::ALL_COLUMNS, true),
        )));

        return in_array('actions', $columns, true) ? $columns : self::DEFAULT_COLUMNS;
    }
}
