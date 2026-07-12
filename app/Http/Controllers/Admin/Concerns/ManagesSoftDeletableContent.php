<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Soft-delete lifecycle for CMS content (trash, restore, force delete).
 */
trait ManagesSoftDeletableContent
{
    protected function moveToTrash(Model $model, string $indexRoute, string $message, mixed $parameters = []): RedirectResponse
    {
        $model->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route($indexRoute, $parameters);
    }

    protected function restoreFromTrash(Model $model, string $trashRoute, string $message, mixed $parameters = []): RedirectResponse
    {
        $model->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route($trashRoute, $parameters);
    }

    protected function permanentlyDelete(Model $model, string $trashRoute, string $message, mixed $parameters = []): RedirectResponse
    {
        $model->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route($trashRoute, $parameters);
    }
}
