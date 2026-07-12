<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStatisticRequest;
use App\Http\Requests\Admin\UpdateStatisticRequest;
use App\Models\Statistic;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StatisticController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('statistic');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreStatisticRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $statistic = Statistic::create([
            'status' => $data['status'],
            'value' => $data['value'],
            'year' => $data['year'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $statistic->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Indicator created.')]);

        return $this->toContentBrowser('statistic');
    }

    public function edit(Statistic $statistic): Response
    {
        $statistic->load('translations');

        return Inertia::render('admin/content/form', $this->formData($statistic));
    }

    public function update(UpdateStatisticRequest $request, Statistic $statistic): RedirectResponse
    {
        $data = $request->validated();

        $statistic->update([
            'status' => $data['status'],
            'value' => $data['value'],
            'year' => $data['year'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $statistic->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Indicator updated.')]);

        return $this->toContentBrowser('statistic');
    }

    public function destroy(Statistic $statistic): RedirectResponse
    {
        $statistic->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Indicator deleted.')]);

        return $this->toContentBrowser('statistic');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Statistic $statistic): array
    {
        $translations = [];

        if ($statistic) {
            foreach ($statistic->translations as $translation) {
                $translations[$translation->locale] = [
                    'label' => $translation->label,
                    'unit' => $translation->unit,
                ];
            }
        }

        return $this->contentEntryFormProps(
            'statistic',
            $statistic ? [
                'id' => $statistic->id,
                'status' => $statistic->status->value,
                'value' => $statistic->value,
                'year' => $statistic->year,
                'sort_order' => $statistic->sort_order,
                'translations' => $translations,
            ] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['label'] ?? null))
            ->map(fn (array $translation) => [
                'label' => $translation['label'],
                'unit' => $translation['unit'] ?? null,
            ])
            ->all();
    }
}
