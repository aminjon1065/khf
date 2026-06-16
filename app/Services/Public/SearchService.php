<?php

namespace App\Services\Public;

use App\Models\Document;
use App\Models\Guide;
use App\Models\Leader;
use App\Models\Page;
use App\Models\Post;
use App\Models\Subdivision;
use App\Models\Tender;
use App\Models\Vacancy;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * Search across Posts, Pages, Guides, and Documents for the given query and locale.
     * Returns a unified collection of results.
     *
     * @return Collection<int, array{id: int, type: string, title: string, excerpt: string|null, url: string, date: string|null}>
     */
    public function search(string $query, string $locale, int $limit = 50): Collection
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return collect();
        }

        $results = collect();
        $likeQuery = "%{$query}%";

        // 1. Search Posts
        $posts = Post::published()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('title', 'like', $likeQuery)
                            ->orWhere('excerpt', 'like', $likeQuery)
                            ->orWhere('body', 'like', $likeQuery);
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->latest('published_at')
            ->take($limit)
            ->get()
            ->map(function (Post $post) use ($locale) {
                $translation = $post->translations->first();

                return [
                    'id' => $post->id,
                    'type' => 'post',
                    'title' => $translation?->title ?? '',
                    'excerpt' => $translation?->excerpt ?? str($translation?->body ?? '')->limit(100)->toString(),
                    'url' => route('news.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    'date' => $post->published_at?->format('Y-m-d'),
                ];
            });
        $results = $results->concat($posts);

        // 2. Search Pages
        $pages = Page::published()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('title', 'like', $likeQuery)
                            ->orWhere('content', 'like', $likeQuery);
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->take($limit)
            ->get()
            ->map(function (Page $page) use ($locale) {
                $translation = $page->translations->first();

                return [
                    'id' => $page->id,
                    'type' => 'page',
                    'title' => $translation?->title ?? '',
                    'excerpt' => str(strip_tags($translation?->content ?? ''))->limit(100)->toString(),
                    'url' => route('pages.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    'date' => $page->updated_at?->format('Y-m-d'),
                ];
            });
        $results = $results->concat($pages);

        // 3. Search Guides
        $guides = Guide::query()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('title', 'like', $likeQuery)
                            ->orWhere('summary', 'like', $likeQuery)
                            ->orWhere('content', 'like', $likeQuery);
                    });
            })
            // Exclude trashed, if soft deletes are used
            ->whereNull('deleted_at')
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->take($limit)
            ->get()
            ->map(function (Guide $guide) use ($locale) {
                $translation = $guide->translations->first();

                return [
                    'id' => $guide->id,
                    'type' => 'guide',
                    'title' => $translation?->title ?? '',
                    'excerpt' => $translation?->summary ?? str(strip_tags($translation?->content ?? ''))->limit(100)->toString(),
                    'url' => route('guides.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    'date' => $guide->updated_at?->format('Y-m-d'),
                ];
            });
        $results = $results->concat($guides);

        // 4. Search Documents
        $documents = Document::query()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('name', 'like', $likeQuery)
                            ->orWhere('description', 'like', $likeQuery);
                    });
            })
            ->whereNull('deleted_at')
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->take($limit)
            ->get()
            ->map(function (Document $document) use ($locale) {
                $translation = $document->translations->first();

                return [
                    'id' => $document->id,
                    'type' => 'document',
                    'title' => $translation?->name ?? '',
                    'excerpt' => $translation?->description,
                    'url' => route('documents.index', ['locale' => $locale, 'search' => $translation?->name]),
                    'date' => $document->document_date?->format('Y-m-d') ?? $document->created_at?->format('Y-m-d'),
                ];
            });
        $results = $results->concat($documents);

        // 5. Search Vacancies (ТЗ §20 «н», §38 — civil-service postings are part of site content).
        $vacancies = Vacancy::published()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('title', 'like', $likeQuery)
                            ->orWhere('summary', 'like', $likeQuery)
                            ->orWhere('description', 'like', $likeQuery)
                            ->orWhere('requirements', 'like', $likeQuery)
                            ->orWhere('responsibilities', 'like', $likeQuery);
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->latest('published_at')
            ->take($limit)
            ->get()
            ->map(function (Vacancy $vacancy) use ($locale) {
                $translation = $vacancy->translations->first();

                return [
                    'id' => $vacancy->id,
                    'type' => 'vacancy',
                    'title' => $translation?->title ?? '',
                    'excerpt' => $translation?->summary ?? str(strip_tags($translation?->description ?? ''))->limit(100)->toString(),
                    'url' => route('vacancies.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    'date' => $vacancy->published_at?->format('Y-m-d'),
                ];
            });
        $results = $results->concat($vacancies);

        // 6. Search Tenders (ТЗ §9, §38 — procurement notices are part of site content).
        $tenders = Tender::published()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('title', 'like', $likeQuery)
                            ->orWhere('summary', 'like', $likeQuery)
                            ->orWhere('description', 'like', $likeQuery)
                            ->orWhere('requirements', 'like', $likeQuery)
                            ->orWhere('terms', 'like', $likeQuery);
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->latest('published_at')
            ->take($limit)
            ->get()
            ->map(function (Tender $tender) use ($locale) {
                $translation = $tender->translations->first();

                return [
                    'id' => $tender->id,
                    'type' => 'tender',
                    'title' => $translation?->title ?? '',
                    'excerpt' => $translation?->summary ?? str(strip_tags($translation?->description ?? ''))->limit(100)->toString(),
                    'url' => route('tenders.show', ['locale' => $locale, 'slug' => $translation?->slug ?? '']),
                    'date' => $tender->published_at?->format('Y-m-d'),
                ];
            });
        $results = $results->concat($tenders);

        // 7. Search Leaders (ТЗ §20 «г», §38).
        $leaders = Leader::published()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('full_name', 'like', $likeQuery)
                            ->orWhere('position', 'like', $likeQuery)
                            ->orWhere('bio', 'like', $likeQuery);
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(function (Leader $leader) use ($locale) {
                $translation = $leader->translations->first();

                return [
                    'id' => $leader->id,
                    'type' => 'leader',
                    'title' => $translation?->full_name ?? '',
                    'excerpt' => $translation?->position,
                    'url' => route('leadership.index', ['locale' => $locale]),
                    'date' => null,
                ];
            });
        $results = $results->concat($leaders);

        // 8. Search Subdivisions (ТЗ §20 «б», §38).
        $subdivisions = Subdivision::published()
            ->whereHas('translations', function ($q) use ($locale, $likeQuery) {
                $q->where('locale', $locale)
                    ->where(function ($subQ) use ($likeQuery) {
                        $subQ->where('name', 'like', $likeQuery)
                            ->orWhere('functions', 'like', $likeQuery);
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->take($limit)
            ->get()
            ->map(function (Subdivision $subdivision) use ($locale) {
                $translation = $subdivision->translations->first();

                return [
                    'id' => $subdivision->id,
                    'type' => 'subdivision',
                    'title' => $translation?->name ?? '',
                    'excerpt' => $translation?->head,
                    'url' => route('structure.index', ['locale' => $locale]),
                    'date' => null,
                ];
            });
        $results = $results->concat($subdivisions);

        return $results->sortByDesc('date')->take($limit)->values();
    }
}
