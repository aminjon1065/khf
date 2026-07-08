<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\PollType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePollRequest;
use App\Http\Requests\Admin\UpdatePollRequest;
use App\Models\Language;
use App\Models\Poll;
use App\Models\PollOption;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));

        $polls = Poll::query()
            ->with('translations')
            ->withCount('votes')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Poll $poll) => [
                'id' => $poll->id,
                'title' => $poll->translation($locale)?->title ?? '—',
                'type' => $poll->type->value,
                'type_label' => $poll->type->label(),
                'status' => $poll->status->value,
                'status_label' => $poll->status->label(),
                'votes_count' => $poll->votes_count,
                'starts_at' => $poll->starts_at?->toIso8601String(),
                'ends_at' => $poll->ends_at?->toIso8601String(),
                'locales' => $poll->translatedLocales(),
            ]);

        return Inertia::render('admin/polls/index', [
            'polls' => $polls,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/polls/form', $this->formData(null));
    }

    public function store(StorePollRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $poll = Poll::create([
            'type' => $data['type'],
            'status' => $data['status'],
            'starts_at' => filled($data['starts_at'] ?? null) ? $data['starts_at'] : null,
            'ends_at' => filled($data['ends_at'] ?? null) ? $data['ends_at'] : null,
            'show_results' => (bool) ($data['show_results'] ?? true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $poll->upsertTranslations($this->translationsPayload($data));
        $this->syncOptions($poll, $data['options'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Poll created.')]);

        return to_route('admin.polls.index');
    }

    public function edit(Poll $poll): Response
    {
        $poll->load(['translations', 'options.translations']);

        return Inertia::render('admin/polls/form', $this->formData($poll));
    }

    public function update(UpdatePollRequest $request, Poll $poll): RedirectResponse
    {
        $data = $request->validated();

        $poll->update([
            'type' => $data['type'],
            'status' => $data['status'],
            'starts_at' => filled($data['starts_at'] ?? null) ? $data['starts_at'] : null,
            'ends_at' => filled($data['ends_at'] ?? null) ? $data['ends_at'] : null,
            'show_results' => (bool) ($data['show_results'] ?? true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $poll->upsertTranslations($this->translationsPayload($data));
        $this->syncOptions($poll, $data['options'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Poll updated.')]);

        return to_route('admin.polls.index');
    }

    public function destroy(Poll $poll): RedirectResponse
    {
        $poll->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Poll deleted.')]);

        return to_route('admin.polls.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Poll $poll): array
    {
        $translations = [];
        $options = [];

        if ($poll) {
            foreach ($poll->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'description' => $translation->description,
                    'slug' => $translation->slug,
                ];
            }

            $voteCounts = $poll->voteCounts();

            foreach ($poll->options as $option) {
                $optionTranslations = [];

                foreach ($option->translations as $translation) {
                    $optionTranslations[$translation->locale] = [
                        'label' => $translation->label,
                    ];
                }

                $options[] = [
                    'id' => $option->id,
                    'sort_order' => $option->sort_order,
                    'votes_count' => $voteCounts[$option->id] ?? 0,
                    'translations' => $optionTranslations,
                ];
            }
        }

        return [
            'poll' => $poll ? [
                'id' => $poll->id,
                'type' => $poll->type->value,
                'status' => $poll->status->value,
                'starts_at' => $poll->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $poll->ends_at?->format('Y-m-d\TH:i'),
                'show_results' => $poll->show_results,
                'sort_order' => $poll->sort_order,
                'total_votes' => $poll->totalVotes(),
                'translations' => $translations,
                'options' => $options,
            ] : null,
            'types' => array_map(
                fn (PollType $type) => ['value' => $type->value, 'label' => $type->label()],
                PollType::cases(),
            ),
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'defaultLocale' => Language::defaultCode(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['title'] ?? null))
            ->map(fn (array $translation, string $locale) => [
                'title' => $translation['title'],
                'description' => $this->sanitizer->clean($translation['description'] ?? null),
                'slug' => filled($translation['slug'] ?? null)
                    ? $translation['slug']
                    : Str::tajikSlug($translation['title']).'-'.$locale,
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    private function syncOptions(Poll $poll, array $options): void
    {
        $keptIds = [];

        foreach (array_values($options) as $index => $optionData) {
            $option = isset($optionData['id'])
                ? $poll->options()->whereKey($optionData['id'])->first()
                : null;

            if ($option === null) {
                $option = $poll->options()->create([
                    'sort_order' => $optionData['sort_order'] ?? $index,
                ]);
            } else {
                $option->update(['sort_order' => $optionData['sort_order'] ?? $index]);
            }

            $keptIds[] = $option->id;

            $option->upsertTranslations(
                collect($optionData['translations'] ?? [])
                    ->filter(fn (array $translation) => filled($translation['label'] ?? null))
                    ->map(fn (array $translation) => ['label' => $translation['label']])
                    ->all(),
            );
        }

        $poll->options()->whereNotIn('id', $keptIds)->each(function (PollOption $option): void {
            $option->delete();
        });
    }
}
