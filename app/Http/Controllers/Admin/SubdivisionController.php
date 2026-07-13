<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubdivisionRequest;
use App\Http\Requests\Admin\UpdateSubdivisionRequest;
use App\Models\Subdivision;
use App\Services\Admin\ContentEntryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubdivisionController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('subdivision');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreSubdivisionRequest $request): RedirectResponse
    {
        $this->entries->store('subdivision', $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subdivision created.')]);

        return $this->toContentBrowser('subdivision');
    }

    public function edit(Subdivision $subdivision): Response
    {
        return Inertia::render('admin/content/form', $this->formData($subdivision));
    }

    public function update(UpdateSubdivisionRequest $request, Subdivision $subdivision): RedirectResponse
    {
        $this->entries->update('subdivision', $subdivision, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subdivision updated.')]);

        return $this->toContentBrowser('subdivision');
    }

    public function destroy(Subdivision $subdivision): RedirectResponse
    {
        $this->entries->destroy('subdivision', $subdivision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subdivision deleted.')]);

        return $this->toContentBrowser('subdivision');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Subdivision $subdivision): array
    {
        $locale = app()->getLocale();

        $parents = Subdivision::query()
            ->with('translations')
            ->when($subdivision, fn (Builder $query) => $query->whereKeyNot($subdivision->id))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Subdivision $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->translation($locale)?->name ?? "#{$candidate->id}",
            ])
            ->all();

        return $this->contentEntryFormProps(
            'subdivision',
            $subdivision ? $this->entries->entryArray($subdivision, 'subdivision') : null,
            [
                'parent_id' => $parents,
            ],
        );
    }
}
