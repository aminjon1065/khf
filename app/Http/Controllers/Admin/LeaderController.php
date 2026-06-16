<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaderRequest;
use App\Http\Requests\Admin\UpdateLeaderRequest;
use App\Models\Language;
use App\Models\Leader;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderController extends Controller
{
    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort') === 'created_at' ? 'created_at' : 'sort_order';
        $direction = (string) $request->string('direction') === 'desc' ? 'desc' : 'asc';

        $leaders = Leader::query()
            ->with(['translations', 'media'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('full_name', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Leader $leader) => [
                'id' => $leader->id,
                'full_name' => $leader->translation($locale)?->full_name ?? '—',
                'position' => $leader->translation($locale)?->position,
                'status' => $leader->status->value,
                'status_label' => $leader->status->label(),
                'photo_url' => $leader->getFirstMediaUrl(Leader::PHOTO_COLLECTION, 'thumb') ?: null,
                'locales' => $leader->translatedLocales(),
                'sort_order' => $leader->sort_order,
            ]);

        return Inertia::render('admin/leadership/index', [
            'leaders' => $leaders,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/leadership/form', $this->formData(null));
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

        return to_route('admin.leadership.index');
    }

    public function edit(Leader $leader): Response
    {
        $leader->load(['translations', 'media']);

        return Inertia::render('admin/leadership/form', $this->formData($leader));
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

        return to_route('admin.leadership.index');
    }

    public function destroy(Leader $leader): RedirectResponse
    {
        $leader->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leader deleted.')]);

        return to_route('admin.leadership.index');
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

        return [
            'leader' => $leader ? [
                'id' => $leader->id,
                'status' => $leader->status->value,
                'sort_order' => $leader->sort_order,
                'email' => $leader->email,
                'phone' => $leader->phone,
                'photo_url' => $leader->getFirstMediaUrl(Leader::PHOTO_COLLECTION, 'thumb') ?: null,
                'translations' => $translations,
            ] : null,
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
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
