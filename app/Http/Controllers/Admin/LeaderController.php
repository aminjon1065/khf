<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaderRequest;
use App\Http\Requests\Admin\UpdateLeaderRequest;
use App\Models\Leader;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('leader');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreLeaderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $leader = Leader::create([
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        $leader->upsertTranslations($this->translationsPayload($data));
        $this->syncPhoto($request, $leader);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leader created.')]);

        return $this->toContentBrowser('leader');
    }

    public function edit(Leader $leader): Response
    {
        $leader->load(['translations', 'media']);

        return Inertia::render('admin/content/form', $this->formData($leader));
    }

    public function update(UpdateLeaderRequest $request, Leader $leader): RedirectResponse
    {
        $data = $request->validated();

        $leader->update([
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        $leader->upsertTranslations($this->translationsPayload($data));
        $this->syncPhoto($request, $leader);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leader updated.')]);

        return $this->toContentBrowser('leader');
    }

    public function destroy(Leader $leader): RedirectResponse
    {
        $leader->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leader deleted.')]);

        return $this->toContentBrowser('leader');
    }

    /**
     * Set the portrait from an upload, or clear it when the "remove" flag is set (ТЗ §20 «г»).
     */
    private function syncPhoto(Request $request, Leader $leader): void
    {
        if ($request->hasFile('photo')) {
            $leader->addMediaFromRequest('photo')->toMediaCollection(Leader::PHOTO_COLLECTION);
        } elseif ($request->boolean('remove_photo')) {
            $leader->clearMediaCollection(Leader::PHOTO_COLLECTION);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Leader $leader): array
    {
        $translations = [];

        if ($leader) {
            foreach ($leader->translations as $translation) {
                $translations[$translation->locale] = [
                    'full_name' => $translation->full_name,
                    'position' => $translation->position,
                    'bio' => $translation->bio,
                    'reception' => $translation->reception,
                ];
            }
        }

        return $this->contentEntryFormProps(
            'leader',
            $leader ? [
                'id' => $leader->id,
                'status' => $leader->status->value,
                'sort_order' => $leader->sort_order,
                'email' => $leader->email,
                'phone' => $leader->phone,
                'translations' => $translations,
            ] : null,
            [],
            [
                'photoUrl' => $leader?->getFirstMediaUrl(Leader::PHOTO_COLLECTION, 'thumb') ?: null,
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
            ->filter(fn (array $translation) => filled($translation['full_name'] ?? null))
            ->map(fn (array $translation) => [
                'full_name' => $translation['full_name'],
                'position' => $translation['position'] ?? '',
                'bio' => $this->sanitizer->clean($translation['bio'] ?? null),
                'reception' => $translation['reception'] ?? null,
            ])
            ->all();
    }
}
