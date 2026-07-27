<?php

namespace Codemonster\Cms\Modules\AdminUsers\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;

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

        $now = date('Y-m-d H:i:s');
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
            'password' => $password,
            'password_confirmation' => (string) $request->input('password_confirmation'),
        ], [
            'username' => 'required|string|min:3|max:60',
            'email' => 'required|email|max:255',
            'is_active' => 'nullable|boolean',
            'password' => $user->id ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string',
        ]);

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

        $user->fill([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'is_active' => in_array((string) $request->input('is_active'), ['1', 'true', 'on'], true),
        ]);

        if ($password !== '') {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }

        $user->save();

        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        return Response::json([
            'message' => 'User saved successfully.',
            'user' => $this->payload($user),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => (string) $user->username,
            'email' => (string) $user->email,
            'is_active' => (bool) $user->is_active,
            'created_at' => $user->created_at?->format(DATE_ATOM),
            'updated_at' => $user->updated_at?->format(DATE_ATOM),
        ];
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
