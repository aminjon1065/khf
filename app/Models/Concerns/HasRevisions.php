<?php

namespace App\Models\Concerns;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Provides version history functionality.
 *
 * @phpstan-require-extends Model
 */
trait HasRevisions
{
    /**
     * @return MorphMany<Revision, $this>
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->orderByDesc('created_at');
    }

    /**
     * Capture the current state of the model and its translations into a revision.
     */
    public function saveRevision(?int $userId = null): Revision
    {
        $userId ??= auth()->id();

        // Ensure translations are loaded
        $this->loadMissing('translations');

        $payload = [
            'attributes' => $this->getAttributes(),
            'translations' => $this->translations->map(fn (Model $translation) => $translation->getAttributes())->all(),
        ];

        return $this->revisions()->create([
            'user_id' => $userId,
            'payload' => $payload,
        ]);
    }

    /**
     * Restore the model to a specific revision.
     */
    public function restoreRevision(Revision $revision): void
    {
        if ($revision->revisionable_type !== static::class || $revision->revisionable_id !== $this->getKey()) {
            throw new \InvalidArgumentException('Revision does not belong to this model.');
        }

        $payload = $revision->payload;

        // Restore main attributes
        if (isset($payload['attributes'])) {
            $this->forceFill($payload['attributes']);
            $this->save();
        }

        // Restore translations
        if (isset($payload['translations']) && in_array(HasTranslations::class, class_uses_recursive(static::class))) {
            // Delete current translations that aren't in the revision
            $locales = array_column($payload['translations'], 'locale');
            $this->translations()->whereNotIn('locale', $locales)->delete();

            // Upsert translations from the revision
            foreach ($payload['translations'] as $translationAttrs) {
                $locale = $translationAttrs['locale'];
                unset($translationAttrs['id']); // Don't try to force ID
                $this->translations()->updateOrCreate(['locale' => $locale], $translationAttrs);
            }
        }
    }
}
