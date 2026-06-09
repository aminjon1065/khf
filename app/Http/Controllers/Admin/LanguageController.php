<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLanguageRequest;
use App\Http\Requests\Admin\UpdateLanguageRequest;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['sort_order', 'code', 'name', 'native_name'];

    /**
     * Paginated, searchable, sortable list of portal languages (ТЗ §7.11, §14).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'sort_order';
        $direction = (string) $request->string('direction') === 'desc' ? 'desc' : 'asc';

        $languages = Language::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('native_name', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/languages/index', [
            'languages' => $languages,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureSingleDefault($data);

        Language::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language created.')]);

        return to_route('admin.languages.index');
    }

    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureSingleDefault($data, $language);

        $language->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language updated.')]);

        return to_route('admin.languages.index');
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('The default language cannot be deleted.')]);

            return to_route('admin.languages.index');
        }

        $language->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language deleted.')]);

        return to_route('admin.languages.index');
    }

    /**
     * Keep a single default language: when this record is marked default, clear the flag on others.
     *
     * @param  array<string, mixed>  $data
     */
    private function ensureSingleDefault(array $data, ?Language $current = null): void
    {
        if (empty($data['is_default'])) {
            return;
        }

        Language::query()
            ->when($current, fn (Builder $query): Builder => $query->whereKeyNot($current->getKey()))
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
