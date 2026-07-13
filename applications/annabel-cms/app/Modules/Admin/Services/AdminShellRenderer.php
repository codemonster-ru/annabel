<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Response;
use Codemonster\View\View;

class AdminShellRenderer implements AdminScreenRendererInterface
{
    public function __construct(
        private AdminNavigationRegistry $navigation,
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

    public function renderAuthenticated(
        string $screen,
        ?string $navigationValue = null,
        ?string $pageTitle = null,
    ): Response {
        $navigationValue ??= $screen;

        return $this->render(true, $screen, [
            'navigationValue' => $navigationValue,
            'pageTitle' => $pageTitle ?? $this->navigation->label($navigationValue),
        ]);
    }

    /**
     * @return array{
     *     authenticated: bool,
     *     screen: string,
     *     csrfToken: string,
     *     user: array{id: int|string, username: string, email: string, roles: array<int, string>}|null,
     *     navigation: array<int, array{value: string, label: string, href?: string, children?: array<mixed>}>,
     *     navigationValue: string,
     *     pageTitle: string|null,
     *     resetToken: string|null
     * }
     * @param array<string, mixed> $extra
     */
    public function payload(bool $isAuthenticated, string $screen = 'login', array $extra = []): array
    {
        $navigation = $isAuthenticated ? $this->navigation->navigation() : [];

        return array_merge([
            'authenticated' => $isAuthenticated,
            'screen' => $screen,
            'csrfToken' => csrf_token(),
            'user' => $isAuthenticated ? $this->users->current()?->toArray() : null,
            'navigation' => $navigation,
            'navigationValue' => $isAuthenticated ? $screen : '',
            'pageTitle' => $isAuthenticated ? $this->navigation->label($screen) : null,
            'resetToken' => null,
        ], $extra);
    }
}
