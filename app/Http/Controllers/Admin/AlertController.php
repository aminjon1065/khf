<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Enums\SubscriptionTopic;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
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
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('alert');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('alert');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreAlertRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $alert = Alert::create($this->attributes($data));
        $alert->upsertTranslations($this->translationsPayload($data));
        $this->dispatchNotifications($alert);
        $this->saveContentRevision($alert);
        $this->flashContentSaved(__('Alert created.'));

        return $this->toContentBrowser('alert');
    }

    public function edit(Alert $alert): Response
    {
        $alert->load('translations');

        return Inertia::render('admin/content/form', $this->formData($alert));
    }

    public function update(UpdateAlertRequest $request, Alert $alert): RedirectResponse
    {
        $data = $request->validated();

        $alert->update($this->attributes($data));
        $alert->upsertTranslations($this->translationsPayload($data));
        $this->dispatchNotifications($alert);
        $this->saveContentRevision($alert);
        $this->flashContentSaved(__('Alert updated.'));

        return $this->toContentBrowser('alert');
    }

    public function destroy(Alert $alert): RedirectResponse
    {
        return $this->moveToTrash($alert, 'admin.content.index', __('Alert moved to trash.'), 'alert');
    }

    public function restore(Alert $alert): RedirectResponse
    {
        return $this->restoreFromTrash($alert, 'admin.content.index', __('Alert restored.'), ['type' => 'alert', 'trashed' => 1]);
    }

    public function forceDelete(Alert $alert): RedirectResponse
    {
        return $this->permanentlyDelete($alert, 'admin.content.index', __('Alert permanently deleted.'), ['type' => 'alert', 'trashed' => 1]);
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

        return $this->contentEntryFormProps(
            'alert',
            $alert ? [
                'id' => $alert->id,
                'hazard_level' => $alert->hazard_level->value,
                'status' => $alert->status->value,
                'region_id' => $alert->region_id,
                'is_dismissible' => $alert->is_dismissible,
                'starts_at' => $alert->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $alert->ends_at?->format('Y-m-d\TH:i'),
                'translations' => $translations,
            ] : null,
            [
                'hazard_level' => HazardLevel::options(),
                'status' => AlertStatus::options(),
                'region_id' => Region::query()
                    ->with('translations')
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Region $region) => [
                        'id' => $region->id,
                        'name' => $region->translation($locale)?->name ?? $region->code,
                    ])
                    ->all(),
            ],
            [],
            [
                'estimate' => route('admin.alerts.estimate'),
            ],
        );
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
