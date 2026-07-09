<?php

namespace App\Support;

use App\Models\MediaFolder;
use Illuminate\Support\Collection;

class MediaFolderPresenter
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function tree(): array
    {
        /** @var Collection<int, MediaFolder> $folders */
        $folders = MediaFolder::query()
            ->withCount('files')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return self::buildTree($folders);
    }

    /**
     * @param  Collection<int, MediaFolder>  $folders
     * @return list<array<string, mixed>>
     */
    private static function buildTree(Collection $folders, ?int $parentId = null): array
    {
        return $folders
            ->where('parent_id', $parentId)
            ->map(fn (MediaFolder $folder): array => [
                'id' => $folder->id,
                'name' => $folder->name,
                'container' => $folder->container->value,
                'container_label' => $folder->container->label(),
                'parent_id' => $folder->parent_id,
                'files_count' => $folder->files_count,
                'children' => self::buildTree($folders, $folder->id),
            ])
            ->values()
            ->all();
    }
}
