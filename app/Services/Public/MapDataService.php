<?php

namespace App\Services\Public;

use App\Enums\HazardLevel;
use App\Models\Region;

/**
 * Supplies supplementary map layers for the public incident map (ТЗ §6.3).
 */
class MapDataService
{
    /**
     * Regional KCHS offices plotted at oblast centres (same source as the contacts page).
     *
     * @return list<array{id: int, lat: float, lng: float, title: string}>
     */
    public function regionalUnits(string $locale): array
    {
        return Region::query()
            ->whereNull('parent_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Region $region): array => [
                'id' => $region->id,
                'lat' => (float) $region->latitude,
                'lng' => (float) $region->longitude,
                'title' => trans('ui.map.unit_title', [
                    'region' => $region->translation($locale)?->name ?? $region->code,
                ], $locale),
            ])
            ->all();
    }

    /**
     * @return array{type: string, features: list<array<string, mixed>>}
     */
    public function riskZonesGeoJson(string $locale): array
    {
        $features = collect(config('map.risk_zones', []))
            ->map(function (array $zone) use ($locale): array {
                $hazard = HazardLevel::from($zone['hazard']);
                $names = $zone['name'];
                $name = $names[$locale] ?? $names['ru'] ?? $names['en'] ?? $zone['id'];

                return [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $zone['id'],
                        'name' => $name,
                        'color' => $hazard->color(),
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [$zone['ring']],
                    ],
                ];
            })
            ->values()
            ->all();

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}
