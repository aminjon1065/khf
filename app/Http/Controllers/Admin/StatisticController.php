<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStatisticRequest;
use App\Http\Requests\Admin\UpdateStatisticRequest;
use App\Models\Language;
use App\Models\Statistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));

        $statistics = Statistic::query()
            ->with('translations')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('label', 'like', "%{$search}%"),
            ))
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Statistic $statistic) => [
                'id' => $statistic->id,
                'label' => $statistic->translation($locale)?->label ?? '—',
                'value' => $statistic->value,
                'year' => $statistic->year,
                'status' => $statistic->status->value,
                'status_label' => $statistic->status->label(),
                'locales' => $statistic->translatedLocales(),
                'sort_order' => $statistic->sort_order,
            ]);

        return Inertia::render('admin/statistics/index', [
            'statistics' => $statistics,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/statistics/form', $this->formData(null));
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

        return to_route('admin.statistics.index');
    }

    public function edit(Statistic $statistic): Response
    {
        $statistic->load('translations');

        return Inertia::render('admin/statistics/form', $this->formData($statistic));
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

        return to_route('admin.statistics.index');
    }

    public function destroy(Statistic $statistic): RedirectResponse
    {
        $statistic->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Indicator deleted.')]);

        return to_route('admin.statistics.index');
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

        return [
            'statistic' => $statistic ? [
                'id' => $statistic->id,
                'status' => $statistic->status->value,
                'value' => $statistic->value,
                'year' => $statistic->year,
                'sort_order' => $statistic->sort_order,
                'translations' => $translations,
            ] : null,
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
            ->filter(fn (array $translation) => filled($translation['label'] ?? null))
            ->map(fn (array $translation) => [
                'label' => $translation['label'],
                'unit' => $translation['unit'] ?? null,
            ])
            ->all();
    }
}
