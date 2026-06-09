<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Multilingual content via separate `*_translations` tables (ТЗ §9, decision D-2).
 *
 * A model using this trait owns many translation rows (one per locale). By convention the
 * translation model is `<Model>Translation` and the foreign key is `<model>_id`; both can be
 * overridden with the `$translationModel` / `$translationForeignKey` properties.
 *
 * @phpstan-require-extends Model
 */
trait HasTranslations
{
    /**
     * @return HasMany<Model, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModelName(), $this->translationForeignKey());
    }

    /**
     * Translation for a locale, falling back to the configured fallback locale and then to any
     * available translation (ТЗ §14 — graceful missing-translation handling).
     */
    public function translation(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();
    }

    public function hasTranslation(string $locale): bool
    {
        return $this->translations->contains('locale', $locale);
    }

    /**
     * Locale codes for which a translation exists.
     *
     * @return list<string>
     */
    public function translatedLocales(): array
    {
        return $this->translations->pluck('locale')->all();
    }

    /**
     * Create or update translations from a `[locale => attributes]` map.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function upsertTranslations(array $translations): void
    {
        foreach ($translations as $locale => $attributes) {
            $this->translations()->updateOrCreate(['locale' => $locale], $attributes);
        }
    }

    /**
     * @return class-string<Model>
     */
    protected function translationModelName(): string
    {
        /** @var class-string<Model> */
        return property_exists($this, 'translationModel')
            ? $this->translationModel
            : static::class.'Translation';
    }

    protected function translationForeignKey(): string
    {
        return property_exists($this, 'translationForeignKey')
            ? $this->translationForeignKey
            : Str::snake(class_basename($this)).'_id';
    }
}
