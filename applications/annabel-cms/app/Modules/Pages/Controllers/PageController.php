<?php

namespace Codemonster\Cms\Modules\Pages\Controllers;

use Codemonster\Cms\Modules\Pages\Models\Page;
use Codemonster\Cms\Modules\Pages\Services\PageResolver;
use Codemonster\Cms\Modules\Settings\Services\SiteSettings;
use Codemonster\Http\Response;
use Codemonster\View\View;

class PageController
{
    public function __construct(
        private PageResolver $pages,
        private SiteSettings $settings,
        private View $view,
    ) {
    }

    public function index(): Response
    {
        return $this->render($this->pages->home());
    }

    public function show(string $slug): Response
    {
        return $this->render($this->pages->bySlug($slug));
    }

    private function render(?Page $page): Response
    {
        $site = $this->settings->current();
        date_default_timezone_set((string) $site->timezone);

        if (!$page instanceof Page) {
            return new Response($this->view->render('pages::not-found', [
                'title' => 'Page not found',
                'site' => $site,
            ]), 404);
        }

        return new Response($this->view->render('pages::show', [
            'title' => $page->title,
            'meta_description' => $page->meta_description,
            'content' => $page->content,
            'site' => $site,
        ]));
    }
}
