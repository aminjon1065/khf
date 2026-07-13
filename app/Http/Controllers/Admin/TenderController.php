<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenderType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenderRequest;
use App\Http\Requests\Admin\UpdateTenderRequest;
use App\Models\Tender;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TenderController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('tender');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('tender');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreTenderRequest $request): RedirectResponse
    {
        $this->entries->store('tender', $request->validated(), [
            'created_by' => $request->user()->id,
        ]);
        $this->flashContentSaved(__('Tender created.'));

        return $this->toContentBrowser('tender');
    }

    public function edit(Tender $tender): Response
    {
        return Inertia::render('admin/content/form', $this->formData($tender));
    }

    public function update(UpdateTenderRequest $request, Tender $tender): RedirectResponse
    {
        $this->entries->update('tender', $tender, $request->validated());
        $this->flashContentSaved(__('Tender updated.'));

        return $this->toContentBrowser('tender');
    }

    public function destroy(Tender $tender): RedirectResponse
    {
        return $this->moveToTrash($tender, 'admin.content.index', __('Tender moved to trash.'), 'tender');
    }

    public function restore(Tender $tender): RedirectResponse
    {
        return $this->restoreFromTrash($tender, 'admin.content.index', __('Tender restored.'), ['type' => 'tender', 'trashed' => 1]);
    }

    public function forceDelete(Tender $tender): RedirectResponse
    {
        return $this->permanentlyDelete($tender, 'admin.content.index', __('Tender permanently deleted.'), ['type' => 'tender', 'trashed' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Tender $tender): array
    {
        return $this->contentEntryFormProps(
            'tender',
            $tender ? $this->entries->entryArray($tender, 'tender') : null,
            [
                'type' => TenderType::options(),
            ],
        );
    }
}
