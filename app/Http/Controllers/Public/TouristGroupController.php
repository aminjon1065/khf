<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTouristGroupRequest;
use App\Models\Region;
use App\Models\TouristGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TouristGroupController extends Controller
{
    /**
     * Public tourist-group registration form (ТЗ §6.6).
     */
    public function create(): Response
    {
        $locale = app()->getLocale();

        return Inertia::render('public/tourist-groups/create', [
            'regions' => Region::query()
                ->with('translations')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Region $region) => ['id' => $region->id, 'name' => $region->translation($locale)?->name ?? $region->code])
                ->all(),
            'submittedReference' => session('tourist_group_reference'),
        ]);
    }

    public function store(StoreTouristGroupRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['website']);

        $group = TouristGroup::createWithUniqueReference($data);

        return to_route('tourist-groups.create', ['locale' => app()->getLocale()])
            ->with('tourist_group_reference', $group->reference);
    }

    /**
     * Public status tracking by reference number (ТЗ §6.6).
     */
    public function track(Request $request): Response
    {
        $reference = trim((string) $request->string('reference'));
        $result = null;

        if ($reference !== '') {
            $group = TouristGroup::where('reference', $reference)->first();

            $result = $group === null
                ? ['found' => false]
                : [
                    'found' => true,
                    'reference' => $group->reference,
                    'status' => $group->status->label(),
                    'start_date' => $group->start_date?->format('d.m.Y'),
                    'end_date' => $group->end_date?->format('d.m.Y'),
                ];
        }

        return Inertia::render('public/tourist-groups/track', [
            'reference' => $reference,
            'result' => $result,
        ]);
    }
}
