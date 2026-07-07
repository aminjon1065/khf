<?php

namespace App\Services\Public;

use App\Enums\SearchContentType;
use App\Models\Document;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Guide;
use App\Models\Leader;
use App\Models\Page;
use App\Models\Post;
use App\Models\Statistic;
use App\Models\Subdivision;
use App\Models\Tender;
use App\Models\Vacancy;
use App\Support\SearchHighlighter;
use App\Support\TranslationSearch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SearchService
{
    public function __construct(
        private TranslationSearch $translationSearch,
        private SearchHighlighter $highlighter,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query, string $locale, int $limit = 50, ?SearchContentType $type = null): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return collect();
        }

        $results = collect();
        $types = $type !== null ? [$type] : SearchContentType::cases();

        foreach ($types as $contentType) {
            $results = $results->concat(match ($contentType) {
                SearchContentType::Post => $this->searchPosts($query, $locale, $limit),
                SearchContentType::Page => $this->searchPages($query, $locale, $limit),
                SearchContentType::Guide => $this->searchGuides($query, $locale, $limit),
                SearchContentType::Document => $this->searchDocuments($query, $locale, $limit),
                SearchContentType::Vacancy => $this->searchVacancies($query, $locale, $limit),
                SearchContentType::Tender => $this->searchTenders($query, $locale, $limit),
                SearchContentType::Leader => $this->searchLeaders($query, $locale, $limit),
                SearchContentType::Subdivision => $this->searchSubdivisions($query, $locale, $limit),
                SearchContentType::Gallery => $this->searchGalleries($query, $locale, $limit),
                SearchContentType::Faq => $this->searchFaqs($query, $locale, $limit),
                SearchContentType::Statistic => $this->searchStatistics($query, $locale, $limit),
            });
        }

        return $this->withHighlights(
            $results->sortByDesc('date')->take($limit)->values(),
            $query,
        );
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(string $query, string $locale, ?SearchContentType $type, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        $query = trim($query);
        $maxPool = $type !== null ? 500 : 200;

        $items = $query === '' || mb_strlen($query) < 2
            ? collect()
            : $this->search($query, $locale, $maxPool, $type);

        $total = $items->count();
        $pageItems = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPosts(string $query, string $locale, int $limit): Collection
    {
        return Post::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['title', 'excerpt', 'body'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->latest('published_at')
            ->take($limit)
            ->get()
            ->map(function (Post $post) use ($locale): array {
                $translation = $post->translations->first();

                return $this->result(
                    id: $post->id,
                    type: 'post',
                    title: $translation?->title ?? '',
                    excerpt: $translation?->excerpt ?? str($translation?->body ?? '')->limit(100)->toString(),
                    url: route('news.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    date: $post->published_at?->format('Y-m-d'),
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPages(string $query, string $locale, int $limit): Collection
    {
        return Page::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['title', 'content'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->take($limit)
            ->get()
            ->map(function (Page $page) use ($locale): array {
                $translation = $page->translations->first();

                return $this->result(
                    id: $page->id,
                    type: 'page',
                    title: $translation?->title ?? '',
                    excerpt: str(strip_tags($translation?->content ?? ''))->limit(100)->toString(),
                    url: route('pages.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    date: $page->updated_at?->format('Y-m-d'),
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchGuides(string $query, string $locale, int $limit): Collection
    {
        return Guide::query()
            ->whereNull('deleted_at')
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['title', 'summary', 'content'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->take($limit)
            ->get()
            ->map(function (Guide $guide) use ($locale): array {
                $translation = $guide->translations->first();

                return $this->result(
                    id: $guide->id,
                    type: 'guide',
                    title: $translation?->title ?? '',
                    excerpt: $translation?->summary ?? str(strip_tags($translation?->content ?? ''))->limit(100)->toString(),
                    url: route('guides.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    date: $guide->updated_at?->format('Y-m-d'),
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchDocuments(string $query, string $locale, int $limit): Collection
    {
        return Document::query()
            ->whereNull('deleted_at')
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['name', 'description'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->take($limit)
            ->get()
            ->map(function (Document $document) use ($locale): array {
                $translation = $document->translations->first();

                return $this->result(
                    id: $document->id,
                    type: 'document',
                    title: $translation?->name ?? '',
                    excerpt: $translation?->description,
                    url: route('documents.index', ['locale' => $locale, 'search' => $translation?->name]),
                    date: $document->document_date?->format('Y-m-d') ?? $document->created_at?->format('Y-m-d'),
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchVacancies(string $query, string $locale, int $limit): Collection
    {
        return Vacancy::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['title', 'summary', 'description', 'requirements', 'responsibilities'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->latest('published_at')
            ->take($limit)
            ->get()
            ->map(function (Vacancy $vacancy) use ($locale): array {
                $translation = $vacancy->translations->first();

                return $this->result(
                    id: $vacancy->id,
                    type: 'vacancy',
                    title: $translation?->title ?? '',
                    excerpt: $translation?->summary ?? str(strip_tags($translation?->description ?? ''))->limit(100)->toString(),
                    url: route('vacancies.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    date: $vacancy->published_at?->format('Y-m-d'),
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchTenders(string $query, string $locale, int $limit): Collection
    {
        return Tender::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['title', 'summary', 'description', 'requirements', 'terms'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->latest('published_at')
            ->take($limit)
            ->get()
            ->map(function (Tender $tender) use ($locale): array {
                $translation = $tender->translations->first();

                return $this->result(
                    id: $tender->id,
                    type: 'tender',
                    title: $translation?->title ?? '',
                    excerpt: $translation?->summary ?? str(strip_tags($translation?->description ?? ''))->limit(100)->toString(),
                    url: route('tenders.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    date: $tender->published_at?->format('Y-m-d'),
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchLeaders(string $query, string $locale, int $limit): Collection
    {
        return Leader::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['full_name', 'position', 'bio'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(function (Leader $leader) use ($locale): array {
                $translation = $leader->translations->first();

                return $this->result(
                    id: $leader->id,
                    type: 'leader',
                    title: $translation?->full_name ?? '',
                    excerpt: $translation?->position,
                    url: route('leadership.index', ['locale' => $locale]),
                    date: null,
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchSubdivisions(string $query, string $locale, int $limit): Collection
    {
        return Subdivision::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['name', 'functions'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(function (Subdivision $subdivision) use ($locale): array {
                $translation = $subdivision->translations->first();

                return $this->result(
                    id: $subdivision->id,
                    type: 'subdivision',
                    title: $translation?->name ?? '',
                    excerpt: $translation?->head,
                    url: route('structure.index', ['locale' => $locale]),
                    date: null,
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchGalleries(string $query, string $locale, int $limit): Collection
    {
        return Gallery::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['title', 'description'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(function (Gallery $gallery) use ($locale): array {
                $translation = $gallery->translations->first();

                return $this->result(
                    id: $gallery->id,
                    type: 'gallery',
                    title: $translation?->title ?? '',
                    excerpt: $translation?->description,
                    url: route('gallery.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    date: null,
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchFaqs(string $query, string $locale, int $limit): Collection
    {
        return Faq::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['question', 'answer'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(function (Faq $faq) use ($locale): array {
                $translation = $faq->translations->first();

                return $this->result(
                    id: $faq->id,
                    type: 'faq',
                    title: $translation?->question ?? '',
                    excerpt: $translation?->answer ? str(strip_tags($translation->answer))->limit(100)->toString() : null,
                    url: route('faq.index', ['locale' => $locale]),
                    date: null,
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchStatistics(string $query, string $locale, int $limit): Collection
    {
        return Statistic::published()
            ->whereHas('translations', fn ($q) => $this->translationSearch->apply(
                $q,
                $locale,
                ['label'],
                $query,
            ))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(function (Statistic $statistic) use ($locale): array {
                $translation = $statistic->translations->first();

                return $this->result(
                    id: $statistic->id,
                    type: 'statistic',
                    title: $translation?->label ?? '',
                    excerpt: trim($statistic->value.' '.($translation?->unit ?? '')),
                    url: route('statistics.index', ['locale' => $locale]),
                    date: null,
                );
            });
    }

    /**
     * @return array{id: int, type: string, title: string, excerpt: string|null, url: string, date: string|null}
     */
    private function result(int $id, string $type, string $title, ?string $excerpt, string $url, ?string $date): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'excerpt' => $excerpt,
            'url' => $url,
            'date' => $date,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $results
     * @return Collection<int, array<string, mixed>>
     */
    private function withHighlights(Collection $results, string $query): Collection
    {
        return $results->map(function (array $item) use ($query): array {
            $item['highlighted_title'] = $this->highlighter->highlight($item['title'], $query);
            $item['highlighted_excerpt'] = $this->highlighter->highlight($item['excerpt'], $query);

            return $item;
        });
    }
}
