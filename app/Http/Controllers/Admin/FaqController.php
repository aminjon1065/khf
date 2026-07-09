<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Models\Faq;
use App\Models\Language;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    use SavesContentRevisions;

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));

        $faqs = Faq::query()
            ->with('translations')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('question', 'like', "%{$search}%"),
            ))
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Faq $faq) => [
                'id' => $faq->id,
                'question' => $faq->translation($locale)?->question ?? '—',
                'status' => $faq->status->value,
                'status_label' => $faq->status->label(),
                'locales' => $faq->translatedLocales(),
                'sort_order' => $faq->sort_order,
            ]);

        return Inertia::render('admin/faq/index', [
            'faqs' => $faqs,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/faq/form', $this->formData(null));
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

        return to_route('admin.faqs.index');
    }

    public function edit(Faq $faq): Response
    {
        $faq->load('translations');

        return Inertia::render('admin/faq/form', $this->formData($faq));
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

        return to_route('admin.faqs.index');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ deleted.')]);

        return to_route('admin.faqs.index');
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

        return [
            'faq' => $faq ? [
                'id' => $faq->id,
                'status' => $faq->status->value,
                'sort_order' => $faq->sort_order,
                'translations' => $translations,
            ] : null,
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'defaultLocale' => Language::defaultCode(),
        ];
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
