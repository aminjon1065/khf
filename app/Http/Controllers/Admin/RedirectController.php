<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRedirectRequest;
use App\Http\Requests\Admin\UpdateRedirectRequest;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RedirectController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['from_path', 'to_url', 'status_code', 'created_at'];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'created_at';
        $direction = (string) $request->string('direction') === 'desc' ? 'desc' : 'asc';

        $redirects = Redirect::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('from_path', 'like', "%{$search}%")
                        ->orWhere('to_url', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/redirects/index', [
            'redirects' => $redirects,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function store(StoreRedirectRequest $request): RedirectResponse
    {
        Redirect::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Redirect created.')]);

        return to_route('admin.redirects.index');
    }

    public function update(UpdateRedirectRequest $request, Redirect $redirect): RedirectResponse
    {
        $redirect->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Redirect updated.')]);

        return to_route('admin.redirects.index');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $redirect->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Redirect deleted.')]);

        return to_route('admin.redirects.index');
    }
}
