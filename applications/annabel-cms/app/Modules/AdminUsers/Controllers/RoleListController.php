<?php

namespace Codemonster\Cms\Modules\AdminUsers\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\Role;
use Codemonster\Cms\Modules\Auth\Services\PermissionRegistry;
use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\InvalidDateTimeException;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;
use Psr\Clock\ClockInterface;

class RoleListController
{
    private const TABLE_KEY = 'roles';

    /** @var list<string> */
    private const ALL_COLUMNS = ['actions', 'id', 'name', 'code', 'is_active', 'sort_order', 'created_at', 'updated_at'];

    /** @var list<string> */
    private const DEFAULT_COLUMNS = ['actions', 'id', 'name', 'code', 'is_active', 'sort_order', 'created_at', 'updated_at'];

    private const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        private AdminScreenRendererInterface $admin,
        private UserSessionInterface $users,
        private PermissionRegistry $permissions,
        private Validator $validator,
        private ClockInterface $clock,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.roles.list', 'admin.users.roles', 'Roles');
    }

    public function create(): Response
    {
        return $this->admin->renderAuthenticated('admin.roles.list', 'admin.users.roles', 'New role');
    }

    public function edit(string $id): Response
    {
        if (!Role::find($id) instanceof Role) {
            abort(404);
        }

        return $this->admin->renderAuthenticated('admin.roles.list', 'admin.users.roles', 'Edit role');
    }

    public function data(Request $request): Response
    {
        $query = Role::query();
        $query->getBuilder()->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
        $page = $this->positiveInteger($request->query('page'), 1);
        $perPage = $this->positiveInteger($request->query('per_page'), self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $pagination = $query->paginate($perPage, $page);
        $roles = [];

        foreach ($pagination['data'] as $role) {
            $roles[] = $this->payload($role);
        }

        return Response::json([
            'data' => $roles,
            'total' => $pagination['total'],
            'current_page' => $pagination['current_page'],
            'per_page' => $pagination['per_page'],
            'csrf_token' => csrf_token(),
            'visible_columns' => $this->visibleColumns(),
        ]);
    }

    public function updatePreferences(Request $request): Response
    {
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

        $user = $this->users->current();
        if ($user === null) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }
        $userId = $user->id;

        $now = DateTime::now($this->clock, 'UTC')->format(DateTime::DATABASE_FORMAT);
        $preferences = db()->table('admin_table_preferences')
            ->where('user_id', $userId)
            ->where('table_key', self::TABLE_KEY)
            ->first();

        $data = [
            'visible_columns' => json_encode($columns, JSON_THROW_ON_ERROR),
            'updated_at' => $now,
        ];
        if ($preferences) {
            db()->table('admin_table_preferences')->where('id', $preferences['id'])->update($data);
        } else {
            db()->table('admin_table_preferences')->insert($data + [
                'user_id' => $userId,
                'table_key' => self::TABLE_KEY,
                'created_at' => $now,
            ]);
        }

        return Response::json(['visible_columns' => $columns]);
    }

    public function showData(string $id): Response
    {
        $role = Role::find($id);
        if (!$role instanceof Role) {
            return Response::json(['message' => 'Role not found.'], 404);
        }

        return Response::json([
            'role' => $this->payload($role),
            'permissions' => $this->permissionOptionPayload($role),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function permissionOptions(): Response
    {
        return Response::json([
            'permissions' => $this->permissionOptionPayload(),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, new Role());
    }

    public function update(Request $request, string $id): Response
    {
        $role = Role::find($id);
        if (!$role instanceof Role) {
            return Response::json(['message' => 'Role not found.'], 404);
        }

        return $this->save($request, $role);
    }

    public function destroy(string $id): Response
    {
        $role = Role::find($id);
        if (!$role instanceof Role) {
            return Response::json(['message' => 'Role not found.'], 404);
        }
        if ((string) $role->code === 'admin') {
            return Response::json(['message' => 'The admin role cannot be deleted.'], 422);
        }
        if (db()->table('role_user')->where('role_id', $role->id)->exists()) {
            return Response::json(['message' => 'Role cannot be deleted while assigned to users.'], 422);
        }

        $role->delete();

        return Response::json(['message' => 'Role deleted successfully.']);
    }

    private function save(Request $request, Role $role): Response
    {
        $validated = $this->validator->validateOrFail([
            'name' => trim((string) $request->input('name')),
            'code' => trim((string) $request->input('code')),
            'description' => trim((string) $request->input('description')),
            'is_active' => $request->input('is_active'),
            'active_from' => trim((string) $request->input('active_from')),
            'active_until' => trim((string) $request->input('active_until')),
            'sort_order' => $request->input('sort_order'),
        ], ['name' => 'required|string|min:2|max:60', 'code' => 'required|string|min:2|max:60', 'description' => 'nullable|string|max:255', 'is_active' => 'nullable|boolean', 'active_from' => 'nullable|string', 'active_until' => 'nullable|string', 'sort_order' => 'nullable|integer|min:1|max:1000000']);

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

        $permissionCodes = $this->validatedPermissionCodes($request);

        if ($permissionCodes instanceof Response) {
            return $permissionCodes;
        }

        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9 _-]{1,59}\z/', $validated['name']) !== 1) {
            return $this->invalid('name', 'Role name may contain only letters, numbers, spaces, underscores, or hyphens and must start with a letter or number.');
        }
        if (preg_match('/\A[a-z0-9][a-z0-9_-]{1,59}\z/', $validated['code']) !== 1) {
            return $this->invalid('code', 'Code may contain only lowercase letters, numbers, underscores, or hyphens and must start with a letter or number.');
        }
        if ((string) $role->code === 'admin' && $validated['name'] !== 'Admin') {
            return $this->invalid('name', 'The admin role cannot be renamed.');
        }
        if ((string) $role->code === 'admin' && $validated['code'] !== 'admin') {
            return $this->invalid('code', 'The admin role code cannot be changed.');
        }

        $duplicate = Role::query()->where('name', $validated['name'])->where('id', '!=', $role->id ?? 0)->first();
        if ($duplicate instanceof Role) {
            return $this->invalid('name', 'This role name is already in use.');
        }
        $duplicateCode = Role::query()->where('code', $validated['code'])->where('id', '!=', $role->id ?? 0)->first();
        if ($duplicateCode instanceof Role) {
            return $this->invalid('code', 'This role code is already in use.');
        }

        transaction(function () use ($request, $validated, $activeFrom, $activeUntil, $permissionCodes, $role): void {
            $role->fill([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'] !== '' ? $validated['description'] : null,
                'is_active' => in_array((string) $request->input('is_active'), ['1', 'true', 'on'], true),
                'active_from' => $activeFrom,
                'active_until' => $activeUntil,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);
            $role->save();
            $this->syncPermissions($role, $permissionCodes);
        });

        return Response::json([
            'message' => 'Role saved successfully.',
            'role' => $this->payload($role),
            'permissions' => $this->permissionOptionPayload($role),
        ]);
    }

    /** @return list<string>|Response */
    private function validatedPermissionCodes(Request $request): array|Response
    {
        $value = $request->input('permissions');

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return $this->invalid('permissions', 'The selected permissions must be a valid list.');
        }

        $codes = array_values(array_unique(array_filter(
            $value,
            static fn (mixed $code): bool => is_string($code) && $code !== '',
        )));

        if (count($codes) !== count($value)) {
            return $this->invalid('permissions', 'A selected permission is invalid.');
        }

        if ($codes === []) {
            return [];
        }

        foreach ($codes as $code) {
            if (!$this->permissions->has($code)) {
                return $this->invalid('permissions', 'A selected permission does not exist.');
            }
        }

        return $codes;
    }

    /** @param list<string> $codes */
    private function syncPermissions(Role $role, array $codes): void
    {
        $knownCodes = array_column($this->permissions->all(), 'code');

        if ($knownCodes !== []) {
            db()->table('role_permission')
                ->where('role_id', $role->id)
                ->whereIn('permission', $knownCodes)
                ->delete();
        }

        if ((string) $role->code === 'admin' || $codes === []) {
            return;
        }

        foreach ($codes as $code) {
            db()->table('role_permission')->insert([
                'role_id' => $role->id,
                'permission' => $code,
            ]);
        }
    }

    /** @return list<array{id: string, code: string, name: string, category: string, selected: bool, locked: bool}> */
    private function permissionOptionPayload(?Role $role = null): array
    {
        $selected = [];
        $locked = $role !== null && (string) $role->code === 'admin';

        if ($role !== null && !$locked) {
            foreach (db()->table('role_permission')->where('role_id', $role->id)->get() as $assignment) {
                $selected[(string) $assignment['permission']] = true;
            }
        }

        $payload = [];

        foreach ($this->permissions->all() as $permission) {
            $payload[] = [
                'id' => $permission['code'],
                'code' => $permission['code'],
                'name' => $permission['name'],
                'category' => $permission['category'],
                'selected' => $locked || isset($selected[$permission['code']]),
                'locked' => $locked,
            ];
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function payload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => (string) $role->name,
            'code' => (string) $role->code,
            'description' => $role->description !== null ? (string) $role->description : null,
            'is_active' => (bool) $role->is_active,
            'active_from' => $this->toIso8601($role->active_from),
            'active_until' => $this->toIso8601($role->active_until),
            'sort_order' => (int) $role->sort_order,
            'created_at' => $role->created_at?->format(DATE_ATOM),
            'updated_at' => $role->updated_at?->format(DATE_ATOM),
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
        if (!$value instanceof \DateTimeInterface) {
            return '';
        }

        return DateTime::fromInterface($value)
            ->toTimezone('UTC')
            ->format(DATE_ATOM);
    }

    private function invalid(string $field, string $message): Response
    {
        return Response::json(['message' => 'The given data was invalid.', 'errors' => [$field => [$message]]], 422);
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
        $userId = $user->id;
        $preferences = db()->table('admin_table_preferences')->where('user_id', $userId)->where('table_key', self::TABLE_KEY)->first();
        if (!$preferences || !is_string($preferences['visible_columns'] ?? null)) {
            return self::DEFAULT_COLUMNS;
        }
        $columns = json_decode($preferences['visible_columns'], true);
        if (!is_array($columns)) {
            return self::DEFAULT_COLUMNS;
        }
        $columns = array_values(array_unique(array_filter($columns, static fn (mixed $column): bool => is_string($column) && in_array($column, self::ALL_COLUMNS, true))));

        return in_array('actions', $columns, true) ? $columns : self::DEFAULT_COLUMNS;
    }
}
