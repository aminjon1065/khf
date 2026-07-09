<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use App\Models\MediaUsage;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class MediaUsageService
{
    public function syncLibraryReference(HasMedia $model, ?int $mediaFileId, string $context, string $label): void
    {
        MediaUsage::query()
            ->where('usable_type', $model::class)
            ->where('usable_id', $model->getKey())
            ->where('context', $context)
            ->delete();

        if ($mediaFileId === null) {
            return;
        }

        MediaUsage::query()->create([
            'media_file_id' => $mediaFileId,
            'usable_type' => $model::class,
            'usable_id' => $model->getKey(),
            'context' => $context,
            'label' => $label,
        ]);
    }

    public function clearForModel(Model $model, ?string $context = null): void
    {
        $query = MediaUsage::query()
            ->where('usable_type', $model::class)
            ->where('usable_id', $model->getKey());

        if ($context !== null) {
            $query->where('context', $context);
        }

        $query->delete();
    }

    /**
     * @return list<array{label: string, context: string, edit_url: string|null}>
     */
    public function presentFor(MediaFile $mediaFile): array
    {
        return $mediaFile->usages()
            ->latest('updated_at')
            ->get()
            ->map(fn (MediaUsage $usage): array => [
                'label' => $usage->label,
                'context' => $usage->context,
                'edit_url' => $this->editUrlFor($usage),
            ])
            ->all();
    }

    private function editUrlFor(MediaUsage $usage): ?string
    {
        return match ($usage->usable_type) {
            Post::class => route('admin.posts.edit', $usage->usable_id),
            Page::class => route('admin.pages.edit', $usage->usable_id),
            default => null,
        };
    }

    public function labelForCover(HasMedia $model): string
    {
        $title = null;

        if ($model instanceof Post || $model instanceof Page) {
            $title = $model->translation()?->title;
        }

        $suffix = $title !== null && $title !== '' ? $title : '#'.$model->getKey();

        return match ($model::class) {
            Post::class => "Обложка материала: {$suffix}",
            Page::class => "Обложка страницы: {$suffix}",
            default => "Использование: {$suffix}",
        };
    }
}
