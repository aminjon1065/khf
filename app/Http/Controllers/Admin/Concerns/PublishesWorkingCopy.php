<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Syncs the published snapshot when content enters the published state.
 */
trait PublishesWorkingCopy
{
    protected function syncPublishedSnapshot(
        Model $model,
        ContentStatus $previousStatus,
        ContentStatus $newStatus,
    ): void {
        if ($newStatus !== ContentStatus::Published) {
            return;
        }

        if ($previousStatus !== ContentStatus::Published && method_exists($model, 'capturePublishedSnapshot')) {
            $model->capturePublishedSnapshot();
        }
    }

    protected function publishWorkingCopy(Model $model): void
    {
        if (method_exists($model, 'capturePublishedSnapshot')) {
            $model->capturePublishedSnapshot();
        }
    }
}
