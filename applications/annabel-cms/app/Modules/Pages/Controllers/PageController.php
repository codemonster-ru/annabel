<?php

namespace Codemonster\Cms\Modules\Pages\Controllers;

use Codemonster\Cms\Modules\Pages\Models\Page;
use Codemonster\Cms\Modules\Pages\Services\PageResolver;
use Codemonster\Http\Response;
use Codemonster\View\View;

class PageController
{
    public function __construct(
        private PageResolver $pages,
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
        if (!$page instanceof Page) {
            return new Response($this->view->render('pages::not-found', [
                'title' => 'Page not found',
            ]), 404);
        }

        return new Response($this->view->render('pages::show', [
            'title' => $page->title,
            'content' => $page->content,
        ]));
    }
}
