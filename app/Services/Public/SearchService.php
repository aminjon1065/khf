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
use App\Services\Public\Search\TranslatableContentSearcher;
use App\Support\SearchHighlighter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SearchService
{
    public function __construct(
        private TranslatableContentSearcher $searcher,
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
            $results = $results->concat($this->searchType($contentType, $query, $locale, $limit));
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
    private function searchType(SearchContentType $type, string $query, string $locale, int $limit): Collection
    {
        return match ($type) {
            SearchContentType::Post => $this->searcher->search(
                Post::class,
                $locale,
                $query,
                ['title', 'excerpt', 'body'],
                $limit,
                fn (Builder $q) => $q->published()->latest('published_at'),
                function (Model $post, string $locale): array {
                    /** @var Post $post */
                    $translation = $post->translations->first();

                    return $this->result(
                        $post->id,
                        'post',
                        $translation?->title ?? '',
                        $translation?->excerpt ?? str($translation?->body ?? '')->limit(100)->toString(),
                        route('news.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                        $post->published_at?->format('Y-m-d'),
                    );
                },
            ),
            SearchContentType::Page => $this->searcher->search(
                Page::class,
                $locale,
                $query,
                ['title', 'content'],
                $limit,
                fn (Builder $q) => $q->published(),
                function (Model $page, string $locale): array {
                    /** @var Page $page */
                    $translation = $page->translations->first();

                    return $this->result(
                        $page->id,
                        'page',
                        $translation?->title ?? '',
                        str(strip_tags($translation?->content ?? ''))->limit(100)->toString(),
                        route('pages.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                        $page->updated_at?->format('Y-m-d'),
                    );
                },
            ),
            SearchContentType::Guide => $this->searcher->search(
                Guide::class,
                $locale,
                $query,
                ['title', 'summary', 'content'],
                $limit,
                fn (Builder $q) => $q->whereNull('deleted_at'),
                function (Model $guide, string $locale): array {
                    /** @var Guide $guide */
                    $translation = $guide->translations->first();

                    return $this->result(
                        $guide->id,
                        'guide',
                        $translation?->title ?? '',
                        $translation?->summary ?? str(strip_tags($translation?->content ?? ''))->limit(100)->toString(),
                        route('guides.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                        $guide->updated_at?->format('Y-m-d'),
                    );
                },
            ),
            SearchContentType::Document => $this->searcher->search(
                Document::class,
                $locale,
                $query,
                ['name', 'description'],
                $limit,
                fn (Builder $q) => $q->whereNull('deleted_at'),
                function (Model $document, string $locale): array {
                    /** @var Document $document */
                    $translation = $document->translations->first();

                    return $this->result(
                        $document->id,
                        'document',
                        $translation?->name ?? '',
                        $translation?->description,
                        route('documents.index', ['locale' => $locale, 'search' => $translation?->name]),
                        $document->document_date?->format('Y-m-d') ?? $document->created_at?->format('Y-m-d'),
                    );
                },
            ),
            SearchContentType::Vacancy => $this->searcher->search(
                Vacancy::class,
                $locale,
                $query,
                ['title', 'summary', 'description', 'requirements', 'responsibilities'],
                $limit,
                fn (Builder $q) => $q->published()->latest('published_at'),
                function (Model $vacancy, string $locale): array {
                    /** @var Vacancy $vacancy */
                    $translation = $vacancy->translations->first();

                    return $this->result(
                        $vacancy->id,
                        'vacancy',
                        $translation?->title ?? '',
                        $translation?->summary ?? str(strip_tags($translation?->description ?? ''))->limit(100)->toString(),
                        route('vacancies.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                        $vacancy->published_at?->format('Y-m-d'),
                    );
                },
            ),
            SearchContentType::Tender => $this->searcher->search(
                Tender::class,
                $locale,
                $query,
                ['title', 'summary', 'description', 'requirements', 'terms'],
                $limit,
                fn (Builder $q) => $q->published()->latest('published_at'),
                function (Model $tender, string $locale): array {
                    /** @var Tender $tender */
                    $translation = $tender->translations->first();

                    return $this->result(
                        $tender->id,
                        'tender',
                        $translation?->title ?? '',
                        $translation?->summary ?? str(strip_tags($translation?->description ?? ''))->limit(100)->toString(),
                        route('tenders.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                        $tender->published_at?->format('Y-m-d'),
                    );
                },
            ),
            SearchContentType::Leader => $this->searcher->search(
                Leader::class,
                $locale,
                $query,
                ['full_name', 'position', 'bio'],
                $limit,
                fn (Builder $q) => $q->published()->orderBy('sort_order'),
                function (Model $leader, string $locale): array {
                    /** @var Leader $leader */
                    $translation = $leader->translations->first();

                    return $this->result(
                        $leader->id,
                        'leader',
                        $translation?->full_name ?? '',
                        $translation?->position,
                        route('leadership.index', ['locale' => $locale]),
                        null,
                    );
                },
            ),
            SearchContentType::Subdivision => $this->searcher->search(
                Subdivision::class,
                $locale,
                $query,
                ['name', 'functions'],
                $limit,
                fn (Builder $q) => $q->published()->orderBy('sort_order'),
                function (Model $subdivision, string $locale): array {
                    /** @var Subdivision $subdivision */
                    $translation = $subdivision->translations->first();

                    return $this->result(
                        $subdivision->id,
                        'subdivision',
                        $translation?->name ?? '',
                        $translation?->head,
                        route('structure.index', ['locale' => $locale]),
                        null,
                    );
                },
            ),
            SearchContentType::Gallery => $this->searcher->search(
                Gallery::class,
                $locale,
                $query,
                ['title', 'description'],
                $limit,
                fn (Builder $q) => $q->published()->orderBy('sort_order'),
                function (Model $gallery, string $locale): array {
                    /** @var Gallery $gallery */
                    $translation = $gallery->translations->first();

                    return $this->result(
                        $gallery->id,
                        'gallery',
                        $translation?->title ?? '',
                        $translation?->description,
                        route('gallery.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                        null,
                    );
                },
            ),
            SearchContentType::Faq => $this->searcher->search(
                Faq::class,
                $locale,
                $query,
                ['question', 'answer'],
                $limit,
                fn (Builder $q) => $q->published()->orderBy('sort_order'),
                function (Model $faq, string $locale): array {
                    /** @var Faq $faq */
                    $translation = $faq->translations->first();

                    return $this->result(
                        $faq->id,
                        'faq',
                        $translation?->question ?? '',
                        $translation?->answer ? str(strip_tags($translation->answer))->limit(100)->toString() : null,
                        route('faq.index', ['locale' => $locale]),
                        null,
                    );
                },
            ),
            SearchContentType::Statistic => $this->searcher->search(
                Statistic::class,
                $locale,
                $query,
                ['label'],
                $limit,
                fn (Builder $q) => $q->published()->orderBy('sort_order'),
                function (Model $statistic, string $locale): array {
                    /** @var Statistic $statistic */
                    $translation = $statistic->translations->first();

                    return $this->result(
                        $statistic->id,
                        'statistic',
                        $translation?->label ?? '',
                        trim($statistic->value.' '.($translation?->unit ?? '')),
                        route('statistics.index', ['locale' => $locale]),
                        null,
                    );
                },
            ),
        };
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
