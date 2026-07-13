<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceCategory;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGovServiceRequest;
use App\Http\Requests\Admin\UpdateGovServiceRequest;
use App\Models\GovService;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GovServiceController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('gov_service');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreGovServiceRequest $request): RedirectResponse
    {
        $this->entries->store('gov_service', $request->validated());

        $this->flashContentSaved(__('Service created.'));

        return $this->toContentBrowser('gov_service');
    }

    public function edit(GovService $govService): Response
    {
        return Inertia::render('admin/content/form', $this->formData($govService));
    }

    public function update(UpdateGovServiceRequest $request, GovService $govService): RedirectResponse
    {
        $this->entries->update('gov_service', $govService, $request->validated());

        $this->flashContentSaved(__('Service updated.'));

        return $this->toContentBrowser('gov_service');
    }

    public function destroy(GovService $govService): RedirectResponse
    {
        $this->entries->destroy('gov_service', $govService);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Service deleted.')]);

        return $this->toContentBrowser('gov_service');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?GovService $service): array
    {
        return $this->contentEntryFormProps(
            'gov_service',
            $service ? $this->entries->entryArray($service, 'gov_service') : null,
            [
                'category' => ServiceCategory::options(),
            ],
        );
    }
}
