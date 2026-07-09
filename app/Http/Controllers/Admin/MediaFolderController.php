<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaContainer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaFolderRequest;
use App\Http\Requests\Admin\UpdateMediaFolderRequest;
use App\Models\MediaFolder;
use App\Support\MediaFolderPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class MediaFolderController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => MediaFolderPresenter::tree(),
        ]);
    }

    public function store(StoreMediaFolderRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $parent = isset($data['parent_id']) ? MediaFolder::find($data['parent_id']) : null;

        $folder = MediaFolder::create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'container' => $parent?->resolvedContainer()
                ?? MediaContainer::from($data['container'] ?? MediaContainer::Public->value),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => MediaFolderPresenter::tree(),
                'folder' => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', 'Папка создана');
    }

    public function update(UpdateMediaFolderRequest $request, MediaFolder $mediaFolder): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $parent = isset($data['parent_id']) ? MediaFolder::find($data['parent_id']) : null;

        $mediaFolder->update([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'container' => $parent?->resolvedContainer()
                ?? MediaContainer::from($data['container'] ?? $mediaFolder->container->value),
            'sort_order' => $data['sort_order'] ?? $mediaFolder->sort_order,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['data' => MediaFolderPresenter::tree()]);
        }

        return redirect()->back()->with('success', 'Папка обновлена');
    }

    public function destroy(MediaFolder $mediaFolder): JsonResponse|RedirectResponse
    {
        $parentId = $mediaFolder->parent_id;

        $mediaFolder->files()->update(['media_folder_id' => $parentId]);

        foreach ($mediaFolder->children as $child) {
            $child->update(['parent_id' => $parentId]);
        }

        $mediaFolder->delete();

        if (request()->wantsJson()) {
            return response()->json(['data' => MediaFolderPresenter::tree()]);
        }

        return redirect()->back()->with('success', 'Папка удалена');
    }
}
