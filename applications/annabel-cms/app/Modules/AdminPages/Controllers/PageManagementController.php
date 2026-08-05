<?php

namespace Codemonster\Cms\Modules\AdminPages\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Contracts\AuthorizationInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Cms\Modules\Pages\Models\Page;
use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\InvalidDateTimeException;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;
use Psr\Clock\ClockInterface;

class PageManagementController
{
    private const TABLE_KEY = 'pages';

    /** @var list<string> */
    private const ALL_COLUMNS = ['actions', 'id', 'title', 'slug', 'is_active', 'sort_order', 'created_at', 'updated_at'];

    /** @var list<string> */
    private const DEFAULT_COLUMNS = ['actions', 'id', 'title', 'slug', 'is_active', 'sort_order', 'created_at', 'updated_at'];

    private const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        private AdminScreenRendererInterface $admin,
        private UserSessionInterface $users,
        private AuthorizationInterface $authorization,
        private Validator $validator,
        private ClockInterface $clock,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.pages');
    }

    public function create(): Response
    {
        return $this->admin->renderAuthenticated('admin.pages', 'admin.pages', 'New page');
    }

    public function edit(string $id): Response
    {
        $page = Page::find($id);

        if (!$page instanceof Page) {
            abort(404);
        }

        $user = $this->users->current();

        if ($user === null || $this->authorization->denies($user, 'pages.update', $page)) {
            abort(403);
        }

        return $this->admin->renderAuthenticated('admin.pages', 'admin.pages', 'Edit page');
    }

    public function data(Request $request): Response
    {
        $query = Page::query();
        $query->getBuilder()->orderBy('sort_order', 'asc')->orderBy('title', 'asc');
        $page = $this->positiveInteger($request->query('page'), 1);
        $perPage = $this->positiveInteger($request->query('per_page'), self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $pagination = $query->paginate($perPage, $page);
        $pages = [];

        foreach ($pagination['data'] as $item) {
            $pages[] = $this->payload($item);
        }

        return Response::json([
            'data' => $pages,
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
            db()->table('admin_table_preferences')
                ->where('id', $preferences['id'])
                ->update([
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
        $page = Page::find($id);

        if (!$page instanceof Page) {
            return Response::json(['message' => 'Page not found.'], 404);
        }

        $user = $this->users->current();

        if ($user === null || $this->authorization->denies($user, 'pages.update', $page)) {
            return Response::json(['message' => 'Forbidden'], 403);
        }

        return Response::json([
            'page' => $this->payload($page),
            'owner_options' => $this->authorization->allows($user, 'pages.assign_owner', $page)
                ? $this->ownerOptions()
                : [],
            'csrf_token' => csrf_token(),
        ]);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, new Page());
    }

    public function update(Request $request, string $id): Response
    {
        $page = Page::find($id);

        if (!$page instanceof Page) {
            return Response::json(['message' => 'Page not found.'], 404);
        }

        $user = $this->users->current();

        if ($user === null || $this->authorization->denies($user, 'pages.update', $page)) {
            return Response::json(['message' => 'Forbidden'], 403);
        }

        return $this->save($request, $page);
    }

    public function destroy(string $id): Response
    {
        $page = Page::find($id);

        if (!$page instanceof Page) {
            return Response::json(['message' => 'Page not found.'], 404);
        }

        $user = $this->users->current();

        if ($user === null || $this->authorization->denies($user, 'pages.delete', $page)) {
            return Response::json(['message' => 'Forbidden'], 403);
        }

        $page->delete();

        return Response::json(['message' => 'Page deleted successfully.']);
    }

    private function save(Request $request, Page $page): Response
    {
        $user = $this->users->current();
        if ($user === null) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $this->validator->validateOrFail([
            'slug' => Page::normalizeSlug((string) $request->input('slug')),
            'title' => trim((string) $request->input('title')),
            'sort_order' => $request->input('sort_order'),
            'meta_title' => trim((string) $request->input('meta_title')),
            'meta_description' => trim((string) $request->input('meta_description')),
            'content' => trim((string) $request->input('content')),
            'active_from' => trim((string) $request->input('active_from')),
            'active_until' => trim((string) $request->input('active_until')),
        ], [
            'slug' => 'required|string|max:120',
            'title' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:1|max:1000000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'content' => 'required|string',
            'active_from' => 'nullable|string',
            'active_until' => 'nullable|string',
        ]);

        $activeFrom = $this->toUtc($validated['active_from']);
        $activeUntil = $this->toUtc($validated['active_until']);

        if ($validated['active_from'] !== '' && $activeFrom === null) {
            return $this->invalid('active_from', 'The active from date must be a valid date and time.');
        }

        if ($validated['active_until'] !== '' && $activeUntil === null) {
            return $this->invalid('active_until', 'The active until date must be a valid date and time.');
        }

        if ($activeFrom !== null && $activeUntil !== null && $activeUntil <= $activeFrom) {
            return $this->invalid('active_until', 'The active until date must be after the active from date.');
        }

        if (!Page::validSlug($validated['slug'])) {
            return $this->invalid('slug', 'The slug may contain only lowercase Latin letters, numbers, and hyphens.');
        }

        $duplicate = Page::query()
            ->where('slug', $validated['slug'])
            ->where('id', '!=', $page->id ?? 0)
            ->first();

        if ($duplicate instanceof Page) {
            return $this->invalid('slug', 'This slug is already in use.');
        }

        $isActive = in_array((string) $request->input('is_active'), ['1', 'true', 'on'], true);
        $isNew = !$page->id;
        $currentOwnerId = $page->owner_id ?? $user->id;
        $requestedOwnerId = $request->input('owner_id');

        if ($requestedOwnerId !== null && $requestedOwnerId !== '') {
            if ((!is_int($requestedOwnerId) && !is_string($requestedOwnerId))
                || preg_match('/\A[1-9]\d*\z/', (string) $requestedOwnerId) !== 1
                || !User::find($requestedOwnerId) instanceof User
            ) {
                return $this->invalid('owner_id', 'The selected owner does not exist.');
            }
            if ((string) $requestedOwnerId !== (string) $currentOwnerId
                && $this->authorization->denies($user, 'pages.assign_owner', $page)
            ) {
                return Response::json(['message' => 'Forbidden'], 403);
            }
            $currentOwnerId = $requestedOwnerId;
        }

        $publicationChanged = $isNew
            ? $isActive || $activeFrom !== null || $activeUntil !== null
            : $isActive !== (bool) $page->is_active
                || $activeFrom !== $this->databaseDate($page->active_from)
                || $activeUntil !== $this->databaseDate($page->active_until);

        $authorizationPage = $page;
        if ($isNew) {
            $authorizationPage->owner_id = $currentOwnerId;
        }
        if ($publicationChanged && $this->authorization->denies($user, 'pages.publish', $authorizationPage)) {
            return Response::json(['message' => 'Publishing this page is forbidden.'], 403);
        }

        $attributes = [
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'sort_order' => (int) ($validated['sort_order'] ?? 1),
            'meta_title' => $validated['meta_title'] !== '' ? $validated['meta_title'] : null,
            'meta_description' => $validated['meta_description'] !== '' ? $validated['meta_description'] : null,
            'content' => $validated['content'],
            'is_active' => $isActive,
            'active_from' => $activeFrom,
            'active_until' => $activeUntil,
            'owner_id' => $currentOwnerId,
            'updated_by' => $user->id,
        ];

        if ($isNew) {
            $attributes['created_by'] = $user->id;
        }

        if (!$isActive) {
            $attributes['activated_at'] = null;
        } elseif ($page->activated_at === null) {
            $attributes['activated_at'] = DateTime::now($this->clock, 'UTC')->format(DateTime::DATABASE_FORMAT);
        }

        $page->fill($attributes);
        $page->save();

        return Response::json([
            'message' => 'Page saved successfully.',
            'page' => $this->payload($page),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Page $page): array
    {
        return [
            'id' => $page->id,
            'created_by' => $page->created_by,
            'owner_id' => $page->owner_id,
            'updated_by' => $page->updated_by,
            'slug' => (string) $page->slug,
            'title' => (string) $page->title,
            'sort_order' => (int) $page->sort_order,
            'meta_title' => (string) ($page->meta_title ?? ''),
            'meta_description' => (string) ($page->meta_description ?? ''),
            'content' => (string) $page->content,
            'is_active' => (bool) $page->is_active,
            'created_at' => $page->created_at?->format(DATE_ATOM),
            'activated_at' => $page->activated_at?->format(DATE_ATOM),
            'updated_at' => $page->updated_at?->format(DATE_ATOM),
            'active_from' => $this->toIso8601($page->active_from),
            'active_until' => $this->toIso8601($page->active_until),
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

    private function databaseDate(mixed $value): ?string
    {
        return $value instanceof \DateTimeInterface
            ? DateTime::fromInterface($value)->toTimezone('UTC')->format(DateTime::DATABASE_FORMAT)
            : null;
    }

    /** @return list<array{id: int|string, label: string}> */
    private function ownerOptions(): array
    {
        $users = User::query()->where('is_active', 1);
        $users->getBuilder()->orderBy('username', 'asc');
        $options = [];

        foreach ($users->get() as $user) {
            $options[] = ['id' => $user->id, 'label' => (string) $user->username];
        }

        return $options;
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

        return in_array('actions', $columns, true)
            ? $columns
            : self::DEFAULT_COLUMNS;
    }
}
