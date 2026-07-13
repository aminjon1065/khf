<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVacancyRequest;
use App\Http\Requests\Admin\UpdateVacancyRequest;
use App\Models\Vacancy;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VacancyController extends Controller
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
        return $this->redirectToContentBrowser('vacancy');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('vacancy');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreVacancyRequest $request): RedirectResponse
    {
        $this->entries->store('vacancy', $request->validated(), [
            'created_by' => $request->user()->id,
        ]);
        $this->flashContentSaved(__('Vacancy created.'));

        return $this->toContentBrowser('vacancy');
    }

    public function edit(Vacancy $vacancy): Response
    {
        return Inertia::render('admin/content/form', $this->formData($vacancy));
    }

    public function update(UpdateVacancyRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $this->entries->update('vacancy', $vacancy, $request->validated());
        $this->flashContentSaved(__('Vacancy updated.'));

        return $this->toContentBrowser('vacancy');
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        return $this->moveToTrash($vacancy, 'admin.content.index', __('Vacancy moved to trash.'), 'vacancy');
    }

    public function restore(Vacancy $vacancy): RedirectResponse
    {
        return $this->restoreFromTrash($vacancy, 'admin.content.index', __('Vacancy restored.'), ['type' => 'vacancy', 'trashed' => 1]);
    }

    public function forceDelete(Vacancy $vacancy): RedirectResponse
    {
        return $this->permanentlyDelete($vacancy, 'admin.content.index', __('Vacancy permanently deleted.'), ['type' => 'vacancy', 'trashed' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Vacancy $vacancy): array
    {
        return $this->contentEntryFormProps(
            'vacancy',
            $vacancy ? $this->entries->entryArray($vacancy, 'vacancy') : null,
            [
                'employment_type' => EmploymentType::options(),
            ],
        );
    }
}
