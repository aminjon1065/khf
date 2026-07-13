<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PollType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePollRequest;
use App\Http\Requests\Admin\UpdatePollRequest;
use App\Models\Poll;
use App\Models\PollOption;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('poll');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StorePollRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $poll = $this->entries->store('poll', $data);
        $this->syncOptions($poll, $data['options'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Poll created.')]);

        return $this->toContentBrowser('poll');
    }

    public function edit(Poll $poll): Response
    {
        $poll->load(['translations', 'options.translations']);

        return Inertia::render('admin/content/form', $this->formData($poll));
    }

    public function update(UpdatePollRequest $request, Poll $poll): RedirectResponse
    {
        $data = $request->validated();
        $this->entries->update('poll', $poll, $data);
        $this->syncOptions($poll, $data['options'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Poll updated.')]);

        return $this->toContentBrowser('poll');
    }

    public function destroy(Poll $poll): RedirectResponse
    {
        $this->entries->destroy('poll', $poll);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Poll deleted.')]);

        return $this->toContentBrowser('poll');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Poll $poll): array
    {
        $entry = null;

        if ($poll) {
            $entry = $this->entries->entryArray($poll, 'poll');
            $entry['starts_at'] = $poll->starts_at?->format('Y-m-d\TH:i');
            $entry['ends_at'] = $poll->ends_at?->format('Y-m-d\TH:i');
            $entry['total_votes'] = $poll->totalVotes();

            $voteCounts = $poll->voteCounts();
            $options = [];

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

            $entry['options'] = $options;
        }

        return $this->contentEntryFormProps(
            'poll',
            $entry,
            [
                'type' => PollType::options(),
            ],
        );
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
