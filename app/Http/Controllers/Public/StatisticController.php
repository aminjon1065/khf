<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Statistic;
use Inertia\Inertia;
use Inertia\Response;

class StatisticController extends Controller
{
    /**
     * Public official-statistics page — key activity indicators (ТЗ §20 «у»). Site visit
     * analytics (§64–66) are provided separately via the analytics counter in the footer.
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $statistics = Statistic::published()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Statistic $statistic) => [
                'id' => $statistic->id,
                'value' => $statistic->value,
                'unit' => $statistic->translation($locale)?->unit,
                'label' => $statistic->translation($locale)?->label,
                'year' => $statistic->year,
            ])
            ->filter(fn (array $statistic) => filled($statistic['label']))
            ->values()
            ->all();

        return Inertia::render('public/statistics/index', [
            'statistics' => $statistics,
        ]);
    }
}
