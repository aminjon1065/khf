<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Models\MediaFile;
use App\Support\MediaFilePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $paginator = $this->query($request)->paginate(24)->withQueryString();

        return Inertia::render('admin/media/index', [
            'mediaFiles' => MediaFilePresenter::paginate($paginator),
            'filters' => $this->filters($request),
        ]);
    }

    /**
     * API endpoint to get media for the picker modal.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $paginator = $this->query($request)->paginate(24);

        return response()->json(MediaFilePresenter::paginate($paginator));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $mediaFile = MediaFile::create([
            'user_id' => $request->user()->id,
            'name' => $request->file('file')->getClientOriginalName(),
        ]);

        $mediaFile->addMediaFromRequest('file')->toMediaCollection('default');

        if ($request->wantsJson()) {
            return response()->json(MediaFilePresenter::toArray($mediaFile->fresh()));
        }

        return redirect()->back()->with('success', 'Файл успешно загружен');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediaRequest $request, MediaFile $mediaFile): JsonResponse|RedirectResponse
    {
        $mediaFile->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json(MediaFilePresenter::toArray($mediaFile->fresh()));
        }

        return redirect()->back()->with('success', 'Файл обновлён');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MediaFile $mediaFile): JsonResponse|RedirectResponse
    {
        $mediaFile->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Файл удален');
    }

    /**
     * @return Builder<MediaFile>
     */
    private function query(Request $request): Builder
    {
        $search = trim((string) $request->string('search'));
        $type = (string) $request->string('type');

        return MediaFile::query()
            ->with('media')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            }))
            ->when($type === 'image', fn (Builder $query) => $query->whereHas(
                'media',
                fn (Builder $inner) => $inner->where('mime_type', 'like', 'image/%'),
            ))
            ->when($type === 'document', fn (Builder $query) => $query->whereHas(
                'media',
                fn (Builder $inner) => $inner->where('mime_type', 'not like', 'image/%'),
            ))
            ->latest();
    }

    /**
     * @return array{search: string, type: string}
     */
    private function filters(Request $request): array
    {
        $type = (string) $request->string('type');

        return [
            'search' => trim((string) $request->string('search')),
            'type' => in_array($type, ['image', 'document'], true) ? $type : '',
        ];
    }
}
