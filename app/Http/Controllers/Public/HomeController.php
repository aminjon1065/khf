<?php

namespace App\Http\Controllers\Public;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Public homepage (ТЗ §6.1): latest news + quick access. Alert banner, operational situation
     * and map widget are wired as those modules land.
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $cacheKey = 'home.latest_posts.'.$locale.'.'.(Post::max('updated_at') ?? 'empty');

        $latestPosts = Cache::remember($cacheKey, 3600, function () use ($locale) {
            return Post::published()
                ->with(['translations', 'category.translations', 'media'])
                ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
                ->orderByDesc('published_at')
                ->limit(6)
                ->get()
                ->map(function (Post $post) use ($locale): array {
                    $translation = $post->translation($locale);

                    return [
                        'title' => $translation?->title,
                        'slug' => $translation?->slug,
                        'excerpt' => $translation?->excerpt,
                        'category' => $post->category?->translation($locale)?->name,
                        'cover_url' => $post->getFirstMediaUrl(Post::COVER_COLLECTION, 'thumb') ?: null,
                        'published_at' => $post->published_at?->format('d.m.Y'),
                    ];
                })
                ->all();
        });

        // Operational-situation summary (ТЗ §5, §6.1) — incident counts by status, surfaced in the
        // homepage hero. Cached on the latest incident change like the incidents archive.
        $incidentVersion = Incident::max('updated_at') ?? 'empty';
        $operational = Cache::remember(
            'home.operational.'.$incidentVersion,
            3600,
            function (): array {
                $counts = Incident::query()
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status');

                return [
                    'active' => (int) ($counts[IncidentStatus::Active->value] ?? 0),
                    'controlled' => (int) ($counts[IncidentStatus::Controlled->value] ?? 0),
                    'resolved' => (int) ($counts[IncidentStatus::Resolved->value] ?? 0),
                ];
            }
        );

        $mapIncidents = Cache::remember(
            'home.map_incidents.'.$locale.'.'.$incidentVersion,
            3600,
            function () use ($locale): array {
                return Incident::query()
                    ->with(['translations', 'region.translations'])
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereIn('status', [IncidentStatus::Active, IncidentStatus::Controlled])
                    ->orderByDesc('occurred_at')
                    ->limit(100)
                    ->get()
                    ->map(function (Incident $incident) use ($locale): array {
                        $translation = $incident->translation($locale);

                        return [
                            'id' => $incident->id,
                            'lat' => (float) $incident->latitude,
                            'lng' => (float) $incident->longitude,
                            'color' => $incident->hazard_level->color(),
                            'title' => $translation?->title ?: '—',
                            'type' => $incident->type->label(),
                            'level' => $incident->hazard_level->label(),
                            'status' => $incident->status->label(),
                            'region' => $incident->region?->translation($locale)?->name,
                            'occurred_at' => $incident->occurred_at?->format('d.m.Y H:i'),
                        ];
                    })
                    ->all();
            }
        );

        $homePage = Page::where('is_home', true)->with('translations')->first();
        $blocks = $homePage?->translation($locale)?->blocks ?? [];

        return Inertia::render('public/home', [
            'latestPosts' => $latestPosts,
            'operational' => $operational,
            'mapIncidents' => $mapIncidents,
            'blocks' => $blocks,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'GovernmentOrganization',
                'name' => trans('ui.site.full_name'),
                'alternateName' => trans('ui.site.short_name'),
                'url' => url('/'),
                'logo' => url('/images/emblem-tj.webp'),
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => '112',
                    'contactType' => 'Emergency',
                ],
            ],
        ]);
    }
}
