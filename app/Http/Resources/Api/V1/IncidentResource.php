<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Incident
 */
class IncidentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Incident $incident */
        $incident = $this->resource;
        $translation = $incident->translation();

        return [
            'id' => $incident->id,
            'type' => [
                'value' => $incident->type->value,
                'label' => $incident->type->label(),
            ],
            'hazard_level' => [
                'value' => $incident->hazard_level->value,
                'label' => $incident->hazard_level->label(),
            ],
            'status' => [
                'value' => $incident->status->value,
                'label' => $incident->status->label(),
            ],
            'title' => $translation?->getAttribute('title'),
            'description' => $translation?->getAttribute('description'),
            'location' => [
                'latitude' => $incident->latitude,
                'longitude' => $incident->longitude,
            ],
            'region' => $incident->region?->translation()?->getAttribute('name'),
            'occurred_at' => $incident->occurred_at?->toIso8601String(),
        ];
    }
}
