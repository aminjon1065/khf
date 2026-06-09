<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;

class UpdatePageRequest extends StorePageRequest
{
    /**
     * Exclude the page being edited from the per-locale slug uniqueness check.
     */
    protected function currentPageId(): ?int
    {
        $page = $this->route('page');

        if ($page instanceof Page) {
            return $page->id;
        }

        return is_numeric($page) ? (int) $page : null;
    }
}
