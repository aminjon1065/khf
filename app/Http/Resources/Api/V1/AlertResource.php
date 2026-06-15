<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Alert
 */
class AlertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Alert $alert */
        $alert = $this->resource;
        $translation = $alert->translation();

        return [
            'id' => $alert->id,
            'hazard_level' => [
                'value' => $alert->hazard_level->value,
                'label' => $alert->hazard_level->label(),
            ],
            'title' => $translation?->getAttribute('title'),
            'body' => $translation?->getAttribute('body'),
            'region' => $alert->region?->translation()?->getAttribute('name'),
            'is_dismissible' => $alert->is_dismissible,
            'starts_at' => $alert->starts_at?->toIso8601String(),
            'ends_at' => $alert->ends_at?->toIso8601String(),
        ];
    }
}
