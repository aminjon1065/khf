<?php

namespace App\Http\Controllers\Public;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenderBidRequest;
use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\TenderTranslation;
use App\Support\LocaleUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class TenderController extends Controller
{
    /**
     * Public listing of open procurement tenders for the current locale (ТЗ §9, §20 «э»).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();
        $page = request('page', 1);
        $cacheKey = 'tenders.index.'.$locale.'.page.'.$page.'.'.(Tender::max('updated_at') ?? 'empty');

        $tenders = Cache::remember($cacheKey, 3600, function () use ($locale) {
            return Tender::open()
                ->with('translations')
                ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
                ->orderByDesc('published_at')
                ->paginate(12)
                ->through(fn (Tender $tender) => $this->card($tender, $locale));
        });

        return Inertia::render('public/tenders/index', [
            'tenders' => $tenders,
        ]);
    }

    /**
     * A single published tender resolved by its per-locale slug, with the online bid form
     * (ТЗ §9). Not response-cached — it carries the bid form and its flash receipt.
     */
    public function show(string $locale, string $slug): Response
    {
        $translation = TenderTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        abort_if($translation === null, 404);

        $tender = Tender::published()
            ->whereKey($translation->tender_id)
            ->with('translations')
            ->first();

        abort_if($tender === null, 404);

        $urls = app(LocaleUrls::class)->contentUrls(
            'tenders.show',
            $tender->translations->pluck('slug', 'locale')->all(),
        );

        return Inertia::render('public/tenders/show', [
            'tender' => [
                'id' => $tender->id,
                'tender_number' => $tender->tender_number,
                'title' => $translation->title,
                'organizer' => $translation->organizer,
                'summary' => $translation->summary,
                'description' => $translation->description,
                'requirements' => $translation->requirements,
                'terms' => $translation->terms,
                'type_label' => $tender->type->label(),
                'budget' => $tender->budget !== null ? number_format((float) $tender->budget, 0, '.', ' ') : null,
                'lots_count' => $tender->lots_count,
                'published_at' => $tender->published_at?->format('d.m.Y'),
                'updated_at' => $tender->updated_at?->format('d.m.Y'),
                'deadline_at' => $tender->deadline_at?->format('d.m.Y'),
                'is_open' => $tender->isOpen(),
            ],
            'submittedReference' => session('bid_reference'),
            'localeSwitch' => $urls['switch'],
            'seoAlternates' => $urls['alternates'],
        ]);
    }

    /**
     * Accept an online bid (document + details) for a tender and return its tracking reference as
     * the receipt confirmation (ТЗ §9). Rate-limited at the route.
     */
    public function submitBid(StoreTenderBidRequest $request, string $locale, Tender $tender): RedirectResponse
    {
        abort_unless($tender->status === ContentStatus::Published && $tender->isOpen(), 404);

        $data = $request->validated();
        unset($data['website'], $data['document']);

        $bid = $tender->bids()->create([
            ...$data,
            'reference' => TenderBid::generateReference(),
        ]);
        $bid->addMediaFromRequest('document')->toMediaCollection(TenderBid::DOCUMENT_COLLECTION);

        $slug = $tender->translation($locale)?->slug ?? $tender->translation()?->slug;

        return to_route('tenders.show', ['locale' => $locale, 'slug' => $slug])
            ->with('bid_reference', $bid->reference);
    }

    /**
     * Public status tracking of a bid by reference number (ТЗ §9).
     */
    public function track(Request $request): Response
    {
        $locale = app()->getLocale();
        $reference = trim((string) $request->string('reference'));
        $result = null;

        if ($reference !== '') {
            $bid = TenderBid::with('tender.translations')
                ->where('reference', $reference)
                ->first();

            $result = $bid === null
                ? ['found' => false]
                : [
                    'found' => true,
                    'reference' => $bid->reference,
                    'tender' => $bid->tender?->translation($locale)?->title,
                    'status' => $bid->status->label(),
                    'created_at' => $bid->created_at?->format('d.m.Y'),
                    'updated_at' => $bid->updated_at?->format('d.m.Y'),
                ];
        }

        return Inertia::render('public/tenders/track', [
            'reference' => $reference,
            'result' => $result,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function card(Tender $tender, string $locale): array
    {
        $translation = $tender->translation($locale);

        return [
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'organizer' => $translation?->organizer,
            'summary' => $translation?->summary,
            'tender_number' => $tender->tender_number,
            'type_label' => $tender->type->label(),
            'budget' => $tender->budget !== null ? number_format((float) $tender->budget, 0, '.', ' ') : null,
            'lots_count' => $tender->lots_count,
            'published_at' => $tender->published_at?->format('d.m.Y'),
            'deadline_at' => $tender->deadline_at?->format('d.m.Y'),
        ];
    }
}
