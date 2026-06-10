<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAlertRequest;
use App\Http\Requests\Admin\UpdateAlertRequest;
use App\Jobs\SendAlertNotifications;
use App\Models\Alert;
use App\Models\Language;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['status', 'hazard_level', 'starts_at', 'created_at'];

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'created_at';
        $direction = (string) $request->string('direction') === 'asc' ? 'asc' : 'desc';

        $alerts = Alert::query()
            ->with(['translations', 'region.translations'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Alert $alert) => $this->toRow($alert, $locale));

        return Inertia::render('admin/alerts/index', [
            'alerts' => $alerts,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'trashedCount' => Alert::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $alerts = Alert::onlyTrashed()
            ->with(['translations', 'region.translations'])
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Alert $alert) => $this->toRow($alert, $locale));

        return Inertia::render('admin/alerts/trash', ['alerts' => $alerts]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/alerts/form', $this->formData(null));
    }

    public function store(StoreAlertRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $alert = Alert::create($this->attributes($data));
        $alert->upsertTranslations($this->translationsPayload($data));
        $this->dispatchNotifications($alert);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert created.')]);

        return to_route('admin.alerts.index');
    }

    public function edit(Alert $alert): Response
    {
        $alert->load('translations');

        return Inertia::render('admin/alerts/form', $this->formData($alert));
    }

    public function update(UpdateAlertRequest $request, Alert $alert): RedirectResponse
    {
        $data = $request->validated();

        $alert->update($this->attributes($data));
        $alert->upsertTranslations($this->translationsPayload($data));
        $this->dispatchNotifications($alert);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert updated.')]);

        return to_route('admin.alerts.index');
    }

    public function destroy(Alert $alert): RedirectResponse
    {
        $alert->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert moved to trash.')]);

        return to_route('admin.alerts.index');
    }

    public function restore(Alert $alert): RedirectResponse
    {
        $alert->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert restored.')]);

        return to_route('admin.alerts.trash');
    }

    public function forceDelete(Alert $alert): RedirectResponse
    {
        $alert->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert permanently deleted.')]);

        return to_route('admin.alerts.trash');
    }

    /**
     * Fan out notifications once, when a published alert has not been sent yet (ТЗ §6.4.4).
     */
    private function dispatchNotifications(Alert $alert): void
    {
        if ($alert->status === AlertStatus::Published && $alert->notified_at === null) {
            SendAlertNotifications::dispatch($alert->id);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'hazard_level' => $data['hazard_level'],
            'status' => $data['status'],
            'region_id' => $data['region_id'] ?? null,
            'is_dismissible' => $data['is_dismissible'] ?? true,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Alert $alert, string $locale): array
    {
        return [
            'id' => $alert->id,
            'title' => $alert->translation($locale)?->title ?? '—',
            'locales' => $alert->translatedLocales(),
            'hazard_level' => $alert->hazard_level->value,
            'hazard_label' => $alert->hazard_level->label(),
            'hazard_color' => $alert->hazard_level->color(),
            'status' => $alert->status->value,
            'status_label' => $alert->status->label(),
            'region' => $alert->region?->translation($locale)?->name,
            'starts_at' => $alert->starts_at?->format('d.m.Y H:i'),
            'ends_at' => $alert->ends_at?->format('d.m.Y H:i'),
            'deleted_at' => $alert->deleted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Alert $alert): array
    {
        $locale = app()->getLocale();
        $translations = [];

        if ($alert) {
            foreach ($alert->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'body' => $translation->body,
                ];
            }
        }

        return [
            'alert' => $alert ? [
                'id' => $alert->id,
                'hazard_level' => $alert->hazard_level->value,
                'status' => $alert->status->value,
                'region_id' => $alert->region_id,
                'is_dismissible' => $alert->is_dismissible,
                'starts_at' => $alert->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $alert->ends_at?->format('Y-m-d\TH:i'),
                'translations' => $translations,
            ] : null,
            'levels' => HazardLevel::options(),
            'statuses' => AlertStatus::options(),
            'regions' => Region::query()
                ->with('translations')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Region $region) => ['id' => $region->id, 'name' => $region->translation($locale)?->name ?? $region->code])
                ->all(),
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
            ->map(fn (array $translation) => [
                'title' => $translation['title'],
                'body' => $translation['body'] ?? null,
            ])
            ->all();
    }
}
