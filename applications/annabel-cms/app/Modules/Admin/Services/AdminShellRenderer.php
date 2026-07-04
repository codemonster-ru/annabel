<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Http\Response;
use Codemonster\View\View;

class AdminShellRenderer
{
    public function __construct(
        private ModuleManager $manager,
        private UserSessionInterface $users,
        private AdminAssetManager $assets,
        private View $view,
    ) {
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function render(bool $isAuthenticated, string $screen, array $extra = []): Response
    {
        return new Response($this->view->render('admin::app', [
            'boot' => $this->payload($isAuthenticated, $screen, $extra),
            'assets' => $this->assets->entrypoints(),
        ]));
    }

    /**
     * @return array{
     *     authenticated: bool,
     *     screen: string,
     *     csrfToken: string,
     *     user: array{id: int|string, email: string, roles: array<int, string>}|null,
     *     modules: array<string, string>,
     *     resetToken: string|null
     * }
     * @param array<string, mixed> $extra
     */
    public function payload(bool $isAuthenticated, string $screen = 'login', array $extra = []): array
    {
        return array_merge([
            'authenticated' => $isAuthenticated,
            'screen' => $isAuthenticated ? 'dashboard' : $screen,
            'csrfToken' => csrf_token(),
            'user' => $isAuthenticated ? $this->users->current()?->toArray() : null,
            'modules' => $isAuthenticated ? $this->manager->listAll() : [],
            'resetToken' => null,
        ], $extra);
    }
}
