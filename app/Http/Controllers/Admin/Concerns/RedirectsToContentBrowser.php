<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\RedirectResponse;

/**
 * Canonical list UX for CMS collections is the unified Entry Browser at /admin/content/{type}.
 * Type controllers keep create/edit; index/trash and post-save redirects land on the browser.
 */
trait RedirectsToContentBrowser
{
    protected function redirectToContentBrowser(string $handle): RedirectResponse
    {
        return redirect()->route('admin.content.index', $handle);
    }

    protected function toContentBrowser(string $handle): RedirectResponse
    {
        return to_route('admin.content.index', $handle);
    }

    protected function redirectToContentBrowserTrash(string $handle): RedirectResponse
    {
        return redirect()->route('admin.content.index', [
            'type' => $handle,
            'trashed' => 1,
        ]);
    }
}
