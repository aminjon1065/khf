<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Contacts page (ТЗ §6.9): emergency numbers, regional civil-defense offices plotted on the map,
     * and a link to the electronic reception for feedback.
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $regions = Region::query()
            ->whereNull('parent_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Region $region): array => [
                'id' => $region->id,
                'name' => $region->translation($locale)?->name ?? $region->code,
                'lat' => (float) $region->latitude,
                'lng' => (float) $region->longitude,
            ])
            ->all();

        return Inertia::render('public/contacts', [
            'regions' => $regions,
        ]);
    }
}
