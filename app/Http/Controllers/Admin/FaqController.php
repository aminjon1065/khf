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
use App\Support\HtmlSanitizer;
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

    public function __construct(private HtmlSanitizer $sanitizer) {}

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
        $data = $request->validated();

        $faq = Faq::create([
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $faq->upsertTranslations($this->translationsPayload($data));
        $faq->load('translations');
        $this->saveContentRevision($faq);

        $this->flashContentSaved(__('FAQ created.'));

        return $this->toContentBrowser('faq');
    }

    public function edit(Faq $faq): Response
    {
        $faq->load('translations');

        return Inertia::render('admin/content/form', $this->formData($faq));
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $data = $request->validated();

        $faq->update([
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $faq->upsertTranslations($this->translationsPayload($data));
        $faq->load('translations');
        $this->saveContentRevision($faq);

        $this->flashContentSaved(__('FAQ updated.'));

        return $this->toContentBrowser('faq');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ deleted.')]);

        return $this->toContentBrowser('faq');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Faq $faq): array
    {
        $translations = [];

        if ($faq) {
            foreach ($faq->translations as $translation) {
                $translations[$translation->locale] = [
                    'question' => $translation->question,
                    'answer' => $translation->answer,
                ];
            }
        }

        return $this->contentEntryFormProps(
            'faq',
            $faq ? [
                'id' => $faq->id,
                'status' => $faq->status->value,
                'sort_order' => $faq->sort_order,
                'translations' => $translations,
            ] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['question'] ?? null))
            ->map(fn (array $translation) => [
                'question' => $translation['question'],
                'answer' => $this->sanitizer->clean($translation['answer'] ?? null),
            ])
            ->all();
    }
}
