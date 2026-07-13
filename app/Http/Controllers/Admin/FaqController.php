<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Models\Faq;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('faq');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $this->entries->store('faq', $request->validated());

        $this->flashContentSaved(__('FAQ created.'));

        return $this->toContentBrowser('faq');
    }

    public function edit(Faq $faq): Response
    {
        return Inertia::render('admin/content/form', $this->formData($faq));
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $this->entries->update('faq', $faq, $request->validated());

        $this->flashContentSaved(__('FAQ updated.'));

        return $this->toContentBrowser('faq');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->entries->destroy('faq', $faq);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ deleted.')]);

        return $this->toContentBrowser('faq');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Faq $faq): array
    {
        return $this->contentEntryFormProps(
            'faq',
            $faq ? $this->entries->entryArray($faq, 'faq') : null,
        );
    }
}
