<?php

namespace Codemonster\Cms\Modules\AdminUsers\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\Group;
use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\InvalidDateTimeException;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;
use Psr\Clock\ClockInterface;

class GroupListController
{
    private const TABLE_KEY = 'groups';

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
        private Validator $validator,
        private ClockInterface $clock,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.groups.list', 'admin.users.groups', 'Groups');
    }

    public function create(): Response
    {
        return $this->admin->renderAuthenticated('admin.groups.list', 'admin.users.groups', 'New group');
    }

    public function edit(string $id): Response
    {
        if (!Group::find($id) instanceof Group) {
            abort(404);
        }

        return $this->admin->renderAuthenticated('admin.groups.list', 'admin.users.groups', 'Edit group');
    }

    public function data(Request $request): Response
    {
        $query = Group::query();
        $query->getBuilder()->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
        $page = $this->positiveInteger($request->query('page'), 1);
        $perPage = $this->positiveInteger($request->query('per_page'), self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $pagination = $query->paginate($perPage, $page);
        $groups = [];

        foreach ($pagination['data'] as $group) {
            $groups[] = $this->payload($group);
        }

        return Response::json([
            'data' => $groups,
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
        $group = Group::find($id);
        if (!$group instanceof Group) {
            return Response::json(['message' => 'Group not found.'], 404);
        }

        return Response::json(['group' => $this->payload($group), 'csrf_token' => csrf_token()]);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, new Group());
    }

    public function update(Request $request, string $id): Response
    {
        $group = Group::find($id);
        if (!$group instanceof Group) {
            return Response::json(['message' => 'Group not found.'], 404);
        }

        return $this->save($request, $group);
    }

    public function destroy(string $id): Response
    {
        $group = Group::find($id);
        if (!$group instanceof Group) {
            return Response::json(['message' => 'Group not found.'], 404);
        }
        if ((string) $group->code === 'admin') {
            return Response::json(['message' => 'The admin group cannot be deleted.'], 422);
        }
        if (db()->table('group_user')->where('group_id', $group->id)->exists()) {
            return Response::json(['message' => 'Group cannot be deleted while assigned to users.'], 422);
        }

        $group->delete();

        return Response::json(['message' => 'Group deleted successfully.']);
    }

    private function save(Request $request, Group $group): Response
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

        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9 _-]{1,59}\z/', $validated['name']) !== 1) {
            return $this->invalid('name', 'Group name may contain only letters, numbers, spaces, underscores, or hyphens and must start with a letter or number.');
        }
        if (preg_match('/\A[a-z0-9][a-z0-9_-]{1,59}\z/', $validated['code']) !== 1) {
            return $this->invalid('code', 'Code may contain only lowercase letters, numbers, underscores, or hyphens and must start with a letter or number.');
        }
        if ((string) $group->code === 'admin' && $validated['name'] !== 'Admin') {
            return $this->invalid('name', 'The admin group cannot be renamed.');
        }
        if ((string) $group->code === 'admin' && $validated['code'] !== 'admin') {
            return $this->invalid('code', 'The admin group code cannot be changed.');
        }

        $duplicate = Group::query()->where('name', $validated['name'])->where('id', '!=', $group->id ?? 0)->first();
        if ($duplicate instanceof Group) {
            return $this->invalid('name', 'This group name is already in use.');
        }
        $duplicateCode = Group::query()->where('code', $validated['code'])->where('id', '!=', $group->id ?? 0)->first();
        if ($duplicateCode instanceof Group) {
            return $this->invalid('code', 'This group code is already in use.');
        }

        $group->fill([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'is_active' => in_array((string) $request->input('is_active'), ['1', 'true', 'on'], true),
            'active_from' => $activeFrom,
            'active_until' => $activeUntil,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);
        $group->save();

        return Response::json(['message' => 'Group saved successfully.', 'group' => $this->payload($group)]);
    }

    /** @return array<string, mixed> */
    private function payload(Group $group): array
    {
        return [
            'id' => $group->id,
            'name' => (string) $group->name,
            'code' => (string) $group->code,
            'description' => $group->description !== null ? (string) $group->description : null,
            'is_active' => (bool) $group->is_active,
            'active_from' => $this->toIso8601($group->active_from),
            'active_until' => $this->toIso8601($group->active_until),
            'sort_order' => (int) $group->sort_order,
            'created_at' => $group->created_at?->format(DATE_ATOM),
            'updated_at' => $group->updated_at?->format(DATE_ATOM),
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
