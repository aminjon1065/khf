<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStatisticRequest;
use App\Http\Requests\Admin\UpdateStatisticRequest;
use App\Models\Statistic;
use App\Services\Admin\ContentEntryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StatisticController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('statistic');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreStatisticRequest $request): RedirectResponse
    {
        $this->entries->store('statistic', $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Indicator created.')]);

        return $this->toContentBrowser('statistic');
    }

    public function edit(Statistic $statistic): Response
    {
        return Inertia::render('admin/content/form', $this->formData($statistic));
    }

    public function update(UpdateStatisticRequest $request, Statistic $statistic): RedirectResponse
    {
        $this->entries->update('statistic', $statistic, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Indicator updated.')]);

        return $this->toContentBrowser('statistic');
    }

    public function destroy(Statistic $statistic): RedirectResponse
    {
        $this->entries->destroy('statistic', $statistic);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Indicator deleted.')]);

        return $this->toContentBrowser('statistic');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Statistic $statistic): array
    {
        return $this->contentEntryFormProps(
            'statistic',
            $statistic ? $this->entries->entryArray($statistic, 'statistic') : null,
        );
    }
}
