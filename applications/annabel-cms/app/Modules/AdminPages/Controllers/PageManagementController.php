<?php

namespace Codemonster\Cms\Modules\AdminPages\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Pages\Models\Page;
use Codemonster\Cms\Modules\Settings\Services\SiteSettings;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;

class PageManagementController
{
    private const TABLE_KEY = 'pages';

    /** @var list<string> */
    private const ALL_COLUMNS = ['actions', 'id', 'title', 'slug', 'is_published', 'sort_order', 'created_at', 'updated_at'];

    /** @var list<string> */
    private const DEFAULT_COLUMNS = ['actions', 'id', 'title', 'slug', 'is_published', 'sort_order', 'created_at', 'updated_at'];

    private const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        private AdminScreenRendererInterface $admin,
        private SiteSettings $settings,
        private UserSessionInterface $users,
        private Validator $validator,
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
        if (!Page::find($id) instanceof Page) {
            abort(404);
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

        $now = date('Y-m-d H:i:s');
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

        return Response::json([
            'page' => $this->payload($page),
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

        return $this->save($request, $page);
    }

    public function destroy(string $id): Response
    {
        $page = Page::find($id);

        if (!$page instanceof Page) {
            return Response::json(['message' => 'Page not found.'], 404);
        }

        $page->delete();

        return Response::json(['message' => 'Page deleted successfully.']);
    }

    private function save(Request $request, Page $page): Response
    {
        $validated = $this->validator->validateOrFail([
            'slug' => Page::normalizeSlug((string) $request->input('slug')),
            'title' => trim((string) $request->input('title')),
            'sort_order' => $request->input('sort_order'),
            'meta_title' => trim((string) $request->input('meta_title')),
            'meta_description' => trim((string) $request->input('meta_description')),
            'content' => trim((string) $request->input('content')),
            'publish_at' => trim((string) $request->input('publish_at')),
            'unpublish_at' => trim((string) $request->input('unpublish_at')),
        ], [
            'slug' => 'required|string|max:120',
            'title' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:1|max:1000000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'content' => 'required|string',
            'publish_at' => 'nullable|string',
            'unpublish_at' => 'nullable|string',
        ]);

        $publishAt = $this->toUtc($validated['publish_at']);
        $unpublishAt = $this->toUtc($validated['unpublish_at']);

        if ($validated['publish_at'] !== '' && $publishAt === null) {
            return $this->invalid('publish_at', 'The publish date must be a valid date and time.');
        }

        if ($validated['unpublish_at'] !== '' && $unpublishAt === null) {
            return $this->invalid('unpublish_at', 'The unpublish date must be a valid date and time.');
        }

        if ($publishAt !== null && $unpublishAt !== null && $unpublishAt <= $publishAt) {
            return $this->invalid('unpublish_at', 'The unpublish date must be after the publish date.');
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

        $isPublished = in_array((string) $request->input('is_published'), ['1', 'true', 'on'], true);
        $page->fill([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'sort_order' => (int) ($validated['sort_order'] ?? 1),
            'meta_title' => $validated['meta_title'] !== '' ? $validated['meta_title'] : null,
            'meta_description' => $validated['meta_description'] !== '' ? $validated['meta_description'] : null,
            'content' => $validated['content'],
            'is_published' => $isPublished,
            'published_at' => $isPublished ? ($page->published_at ?? gmdate('Y-m-d H:i:s')) : null,
            'publish_at' => $publishAt,
            'unpublish_at' => $unpublishAt,
        ]);
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
            'slug' => (string) $page->slug,
            'title' => (string) $page->title,
            'sort_order' => (int) $page->sort_order,
            'meta_title' => (string) ($page->meta_title ?? ''),
            'meta_description' => (string) ($page->meta_description ?? ''),
            'content' => (string) $page->content,
            'is_published' => (bool) $page->is_published,
            'created_at' => $page->created_at?->format(DATE_ATOM),
            'published_at' => $page->published_at?->format(DATE_ATOM),
            'updated_at' => $page->updated_at?->format(DATE_ATOM),
            'publish_at' => $this->toLocalInput($page->publish_at),
            'unpublish_at' => $this->toLocalInput($page->unpublish_at),
        ];
    }

    private function toUtc(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat(
            'Y-m-d\\TH:i',
            $value,
            new \DateTimeZone((string) $this->settings->current()->timezone),
        );

        if (!$date || \DateTimeImmutable::getLastErrors() !== false && \DateTimeImmutable::getLastErrors()['warning_count'] > 0) {
            return null;
        }

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function toLocalInput(mixed $value): ?string
    {
        if (!$value instanceof \DateTimeInterface) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new \DateTimeZone((string) $this->settings->current()->timezone))
            ->format('Y-m-d\\TH:i');
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
