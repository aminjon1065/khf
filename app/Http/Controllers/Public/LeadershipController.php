<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Leader;
use Inertia\Inertia;
use Inertia\Response;

class LeadershipController extends Controller
{
    /**
     * Public leadership page — officials with their citizen-reception schedule (ТЗ §20 «г»).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $leaders = Leader::published()
            ->with(['translations', 'media'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Leader $leader) => [
                'id' => $leader->id,
                'full_name' => $leader->translation($locale)?->full_name,
                'position' => $leader->translation($locale)?->position,
                'bio' => $leader->translation($locale)?->bio,
                'reception' => $leader->translation($locale)?->reception,
                'email' => $leader->email,
                'phone' => $leader->phone,
                'photo_url' => $leader->getFirstMediaUrl(Leader::PHOTO_COLLECTION) ?: null,
            ])
            ->all();

        return Inertia::render('public/leadership/index', [
            'leaders' => $leaders,
        ]);
    }
}
