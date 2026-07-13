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
use App\Services\Admin\ContentEntryService;
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

    public function __construct(private ContentEntryService $entries) {}

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
        $leader = $this->entries->store('leader', $request->validated());
        $this->syncPhoto($request, $leader);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leader created.')]);

        return $this->toContentBrowser('leader');
    }

    public function edit(Leader $leader): Response
    {
        $leader->loadMissing('media');

        return Inertia::render('admin/content/form', $this->formData($leader));
    }

    public function update(UpdateLeaderRequest $request, Leader $leader): RedirectResponse
    {
        $this->entries->update('leader', $leader, $request->validated());
        $this->syncPhoto($request, $leader);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leader updated.')]);

        return $this->toContentBrowser('leader');
    }

    public function destroy(Leader $leader): RedirectResponse
    {
        $this->entries->destroy('leader', $leader);

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
        return $this->contentEntryFormProps(
            'leader',
            $leader ? $this->entries->entryArray($leader, 'leader') : null,
            [],
            [
                'photoUrl' => $leader?->getFirstMediaUrl(Leader::PHOTO_COLLECTION, 'thumb') ?: null,
            ],
        );
    }
}
