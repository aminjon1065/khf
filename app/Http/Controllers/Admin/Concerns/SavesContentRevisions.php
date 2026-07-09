<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Inertia\Inertia;

/**
 * Revision snapshots after CMS saves.
 */
trait SavesContentRevisions
{
    protected function saveContentRevision(Model $model): void
    {
        if (method_exists($model, 'saveRevision')) {
            $model->saveRevision();
        }
    }

    protected function flashContentSaved(string $message): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }
}
