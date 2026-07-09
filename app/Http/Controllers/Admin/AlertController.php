<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Enums\SubscriptionTopic;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ListsTranslatableContent;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAlertRequest;
use App\Http\Requests\Admin\UpdateAlertRequest;
use App\Jobs\SendAlertNotifications;
use App\Models\Alert;
use App\Models\Region;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    use BuildsCmsFormData;
    use ListsTranslatableContent;
    use ManagesSoftDeletableContent;
    use SavesContentRevisions;

    /** @var list<string> */
    private const SORTABLE = ['status', 'hazard_level', 'starts_at', 'created_at'];

    public function index(Request $request): Response
    {
        $filters = $this->listFilters($request, 'created_at', 'desc');

        $alerts = $this->paginateTranslatable(
            Alert::query()->with(['translations', 'region.translations']),
            $request,
            self::SORTABLE,
            'created_at',
            'desc',
            fn (Alert $alert, string $locale) => $this->toRow($alert, $locale),
        );

        return Inertia::render('admin/alerts/index', [
            'alerts' => $alerts,
            'filters' => $filters,
            'trashedCount' => Alert::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $alerts = $this->paginateTrashed(
            Alert::onlyTrashed()->with(['translations', 'region.translations']),
            fn (Alert $alert, string $locale) => $this->toRow($alert, $locale),
        );

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
        $this->saveContentRevision($alert);
        $this->flashContentSaved(__('Alert created.'));

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
        $this->saveContentRevision($alert);
        $this->flashContentSaved(__('Alert updated.'));

        return to_route('admin.alerts.index');
    }

    public function destroy(Alert $alert): RedirectResponse
    {
        return $this->moveToTrash($alert, 'admin.alerts.index', __('Alert moved to trash.'));
    }

    public function restore(Alert $alert): RedirectResponse
    {
        return $this->restoreFromTrash($alert, 'admin.alerts.trash', __('Alert restored.'));
    }

    public function forceDelete(Alert $alert): RedirectResponse
    {
        return $this->permanentlyDelete($alert, 'admin.alerts.trash', __('Alert permanently deleted.'));
    }

    public function estimateRecipients(Request $request): JsonResponse
    {
        $regionId = $request->input('region_id');

        $count = Subscriber::confirmed()
            ->whereJsonContains('topics', SubscriptionTopic::Alerts->value)
            ->when($regionId !== null, fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner->whereNull('region_id')->orWhere('region_id', $regionId),
            ))
            ->count();

        return response()->json(['count' => $count]);
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
            'locales' => $this->localeOptions(),
            'defaultLocale' => $this->publicationFormMeta()['defaultLocale'],
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
