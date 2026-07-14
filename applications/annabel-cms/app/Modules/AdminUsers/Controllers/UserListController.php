<?php

namespace Codemonster\Cms\Modules\AdminUsers\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class UserListController
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PAGE = 1_000_000;

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        private AdminScreenRendererInterface $admin,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.users.list');
    }

    public function data(Request $request): Response
    {
        $page = $this->positiveInteger($request->query('page'), 1);
        $perPage = $this->positiveInteger($request->query('per_page'), self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $query = User::query();
        $query->getBuilder()->orderBy('id');
        $pagination = $query->paginate($perPage, $page);
        $users = [];

        foreach ($pagination['data'] as $user) {
            $users[] = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'updated_at' => $user->updated_at?->format(DATE_ATOM),
            ];
        }

        return Response::json([
            'data' => $users,
            'total' => $pagination['total'],
            'current_page' => $pagination['current_page'],
            'per_page' => $pagination['per_page'],
        ]);
    }

    private function positiveInteger(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return min(self::MAX_PAGE, max(1, $value));
        }

        if (!is_string($value) || preg_match('/\A\d+\z/', $value) !== 1) {
            return $default;
        }

        return min(self::MAX_PAGE, max(1, (int) $value));
    }
}
