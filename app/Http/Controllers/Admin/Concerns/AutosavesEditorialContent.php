<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait AutosavesEditorialContent
{
    protected function autosaveResponse(Model $model): JsonResponse
    {
        return response()->json([
            'saved_at' => $model->fresh()->updated_at?->toIso8601String(),
        ]);
    }
}
