<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkMediaRequest;
use App\Http\Requests\Admin\BulkMoveMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Models\Language;
use App\Models\MediaFile;
use App\Models\MediaTag;
use App\Support\MediaFilePresenter;
use App\Support\MediaFolderPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $paginator = $this->query($request)->paginate(24)->withQueryString();

        return Inertia::render('admin/media/index', [
            'mediaFiles' => MediaFilePresenter::paginate($paginator),
            'folders' => MediaFolderPresenter::tree(),
            'locales' => MediaFilePresenter::localeOptions(),
            'defaultLocale' => Language::defaultCode(),
            'filters' => $this->filters($request),
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $paginator = $this->query($request)->paginate(24);

        return response()->json(MediaFilePresenter::paginate($paginator));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);

        $mediaFile = MediaFile::create([
            'user_id' => $request->user()->id,
            'name' => $request->file('file')->getClientOriginalName(),
            'media_folder_id' => $request->integer('folder_id') ?: null,
        ]);

        $mediaFile->addMediaFromRequest('file')->toMediaCollection('default');

        if ($request->wantsJson()) {
            return response()->json(MediaFilePresenter::toArray($mediaFile->fresh(['translations', 'tags', 'usages'])));
        }

        return redirect()->back()->with('success', 'Файл успешно загружен');
    }

    public function update(UpdateMediaRequest $request, MediaFile $mediaFile): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $mediaFile->update([
            'name' => $data['name'],
            'media_folder_id' => $data['media_folder_id'] ?? $mediaFile->media_folder_id,
            'focal_x' => $data['focal_x'] ?? $mediaFile->focal_x,
            'focal_y' => $data['focal_y'] ?? $mediaFile->focal_y,
        ]);

        if (isset($data['translations'])) {
            $mediaFile->upsertTranslations($data['translations']);
            $mediaFile->update([
                'alt_text' => $data['translations'][Language::defaultCode()]['alt_text'] ?? null,
            ]);
        } elseif (array_key_exists('alt_text', $data)) {
            $mediaFile->update(['alt_text' => $data['alt_text']]);
        }

        if (array_key_exists('tags', $data)) {
            $mediaFile->syncTags($data['tags']);
        }

        $mediaFile = $mediaFile->fresh(['translations', 'tags', 'usages', 'media']);

        if ($request->wantsJson()) {
            return response()->json(MediaFilePresenter::toArray($mediaFile));
        }

        return redirect()->back()->with('success', 'Файл обновлён');
    }

    public function destroy(MediaFile $mediaFile): JsonResponse|RedirectResponse
    {
        $mediaFile->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Файл удален');
    }

    public function bulkDestroy(BulkMediaRequest $request): RedirectResponse
    {
        $ids = $request->validated('ids');

        MediaFile::query()->whereIn('id', $ids)->get()->each->delete();

        return redirect()->back()->with('success', 'Выбранные файлы удалены');
    }

    public function bulkMove(BulkMoveMediaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        MediaFile::query()
            ->whereIn('id', $validated['ids'])
            ->update(['media_folder_id' => $validated['folder_id'] ?? null]);

        return redirect()->back()->with('success', 'Файлы перемещены');
    }

    /**
     * @return Builder<MediaFile>
     */
    private function query(Request $request): Builder
    {
        $search = trim((string) $request->string('search'));
        $type = (string) $request->string('type');
        $folderId = (string) $request->string('folder_id');
        $tag = MediaTag::normalizeName((string) $request->string('tag'));

        return MediaFile::query()
            ->with(['media', 'translations', 'tags', 'usages'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhereHas(
                        'translations',
                        fn (Builder $translations) => $translations->where('alt_text', 'like', "%{$search}%"),
                    );
            }))
            ->when($type === 'image', fn (Builder $query) => $query->whereHas(
                'media',
                fn (Builder $inner) => $inner->where('mime_type', 'like', 'image/%'),
            ))
            ->when($type === 'document', fn (Builder $query) => $query->whereHas(
                'media',
                fn (Builder $inner) => $inner->where('mime_type', 'not like', 'image/%'),
            ))
            ->when($folderId === '0', fn (Builder $query) => $query->whereNull('media_folder_id'))
            ->when(
                $folderId !== '' && $folderId !== '0' && $folderId !== 'all',
                fn (Builder $query) => $query->where('media_folder_id', (int) $folderId),
            )
            ->when($tag !== '', fn (Builder $query) => $query->whereHas(
                'tags',
                fn (Builder $inner) => $inner->where('name', $tag),
            ))
            ->latest();
    }

    /**
     * @return array{search: string, type: string, folder_id: string, tag: string}
     */
    private function filters(Request $request): array
    {
        $type = (string) $request->string('type');
        $folderId = (string) $request->string('folder_id');

        return [
            'search' => trim((string) $request->string('search')),
            'type' => in_array($type, ['image', 'document'], true) ? $type : '',
            'folder_id' => in_array($folderId, ['0', 'all'], true) || ctype_digit($folderId)
                ? $folderId
                : 'all',
            'tag' => trim((string) $request->string('tag')),
        ];
    }
}
