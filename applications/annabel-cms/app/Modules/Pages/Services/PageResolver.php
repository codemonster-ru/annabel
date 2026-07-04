<?php

namespace Codemonster\Cms\Modules\Pages\Services;

use Codemonster\Cms\Modules\Pages\Models\Page;

class PageResolver
{
    public function home(): ?Page
    {
        return $this->bySlug('home');
    }

    public function bySlug(string $slug): ?Page
    {
        $slug = Page::normalizeSlug($slug);

        if (!Page::validSlug($slug)) {
            return null;
        }

        return Page::findPublishedBySlug($slug);
    }
}
