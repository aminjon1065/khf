<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Soft-delete lifecycle for CMS content (trash, restore, force delete).
 */
trait ManagesSoftDeletableContent
{
    protected function moveToTrash(Model $model, string $indexRoute, string $message): RedirectResponse
    {
        $model->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route($indexRoute);
    }

    protected function restoreFromTrash(Model $model, string $trashRoute, string $message): RedirectResponse
    {
        $model->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route($trashRoute);
    }

    protected function permanentlyDelete(Model $model, string $trashRoute, string $message): RedirectResponse
    {
        $model->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route($trashRoute);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  class-string<TModel>  $modelClass
     */
    protected function trashedCount(Builder $query, string $modelClass): int
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            return 0;
        }

        return $modelClass::onlyTrashed()->count();
    }
}
