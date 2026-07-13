<?php

namespace App\Services\Admin;

use App\Cms\Blueprint\Blueprint;
use App\Cms\Blueprint\BlueprintField;
use App\Cms\Blueprint\BlueprintRepository;
use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;
use App\Support\BlockSanitizer;
use App\Support\HtmlSanitizer;
use App\Support\PublicationScheduler;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Blueprint-driven create/update/destroy for CMS entry types (A2).
 *
 * Keeps per-type controllers as thin route/permission shims while centralising
 * root-attribute extraction, translation payloads, and revision snapshots.
 */
class ContentEntryService
{
    public function __construct(
        private ContentTypeRegistry $types,
        private BlueprintRepository $blueprints,
        private HtmlSanitizer $sanitizer,
        private BlockSanitizer $blockSanitizer,
    ) {}

    public function definition(string $handle): ContentTypeDefinition
    {
        return $this->types->get($handle);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $extras  Non-blueprint attributes (e.g. created_by)
     */
    public function store(
        string $handle,
        array $validated,
        array $extras = [],
        bool $saveRevision = true,
    ): Model {
        $type = $this->definition($handle);
        $validated = $this->normalizePublicationIfNeeded($type, $validated);

        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;

        $entry = $modelClass::query()->create([
            ...$this->rootAttributes($type, $validated, withDefaults: true),
            ...$extras,
        ]);
        $entry->upsertTranslations($this->translationsPayload($type, $validated, $entry));
        $entry->load('translations');

        if ($saveRevision) {
            $this->saveRevisionIfSupported($entry);
        }

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(
        string $handle,
        Model $entry,
        array $validated,
        bool $saveRevision = true,
    ): Model {
        $type = $this->definition($handle);
        $validated = $this->normalizePublicationIfNeeded($type, $validated);

        $attributes = $this->rootAttributes($type, $validated, withDefaults: false);

        if ($attributes !== []) {
            $entry->update($attributes);
        }

        $entry->upsertTranslations($this->translationsPayload($type, $validated, $entry));
        $entry->load('translations');

        if ($saveRevision) {
            $this->saveRevisionIfSupported($entry);
        }

        return $entry;
    }

    public function destroy(string $handle, Model $entry): void
    {
        $this->definition($handle);
        $entry->delete();
    }

    /**
     * Serialize an entry for the shared Inertia content form.
     *
     * @return array<string, mixed>
     */
    public function entryArray(Model $entry, string $handle): array
    {
        $type = $this->definition($handle);
        $blueprint = $this->blueprint($type);
        $entry->loadMissing('translations');

        $payload = ['id' => $entry->getKey()];

        foreach ($this->rootFields($blueprint, $entry) as $field) {
            $value = $entry->getAttribute($field->handle);
            $payload[$field->handle] = $this->serializeFieldValue($field, $value);
        }

        $localizable = $this->localizableFields($blueprint);
        $translations = [];

        foreach ($entry->translations as $translation) {
            $row = [];

            foreach ($localizable as $field) {
                $row[$field->handle] = $translation->getAttribute($field->handle);
            }

            $translations[$translation->locale] = $row;
        }

        $payload['translations'] = $translations;

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function rootAttributes(
        ContentTypeDefinition $type,
        array $data,
        bool $withDefaults = true,
    ): array {
        $blueprint = $this->blueprint($type);

        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;
        $model = new $modelClass;
        $fillable = array_flip($model->getFillable());
        $attributes = [];

        foreach ($this->rootFields($blueprint, $model) as $field) {
            if (! isset($fillable[$field->handle])) {
                continue;
            }

            if (! array_key_exists($field->handle, $data)) {
                if (! $withDefaults) {
                    continue;
                }

                if ($field->handle === 'sort_order') {
                    $attributes['sort_order'] = 0;
                }

                if ($field->type === 'toggle' && array_key_exists('default', $field->config)) {
                    $attributes[$field->handle] = (bool) $field->config['default'];
                }

                continue;
            }

            $value = $data[$field->handle];

            if ($field->type === 'number' && ($value === '' || $value === null)) {
                $value = null;
            }

            if ($field->handle === 'sort_order' && $value === null) {
                $value = 0;
            }

            if ($field->type === 'toggle') {
                $value = (bool) $value;
            }

            if ($field->type === 'select' && $value === '') {
                $value = null;
            }

            if (in_array($field->type, ['date', 'datetime'], true) && ! filled($value)) {
                $value = null;
            }

            $attributes[$field->handle] = $this->castAttribute($model, $field->handle, $value);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    public function translationsPayload(ContentTypeDefinition $type, array $data, ?Model $except = null): array
    {
        $blueprint = $this->blueprint($type);
        $localizable = $this->localizableFields($blueprint);
        $titleKey = $type->listSearchField;

        return collect($data['translations'] ?? [])
            ->filter(fn (mixed $translation): bool => is_array($translation) && filled($translation[$titleKey] ?? null))
            ->map(function (array $translation, string $locale) use ($localizable, $titleKey, $type, $except): array {
                $row = [];

                foreach ($localizable as $field) {
                    $value = $translation[$field->handle] ?? null;

                    if ($field->type === 'slug') {
                        if (! filled($value)) {
                            $base = Str::tajikSlug((string) ($translation[$titleKey] ?? ''));
                            $value = ($base !== '' ? $base : $type->handle).'-'.$locale;
                        }

                        $value = $this->uniqueTranslationSlug($type, $locale, (string) $value, $except);
                    }

                    if (in_array($field->type, ['rich_text', 'textarea'], true)) {
                        $value = $this->sanitizer->clean(is_string($value) ? $value : null);
                    }

                    if ($field->type === 'blocks') {
                        $value = $this->blockSanitizer->sanitize(is_array($value) ? $value : null);
                    }

                    $row[$field->handle] = $value;
                }

                return $row;
            })
            ->all();
    }

    private function uniqueTranslationSlug(
        ContentTypeDefinition $type,
        string $locale,
        string $base,
        ?Model $except,
    ): string {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;
        $model = new $modelClass;

        if (! method_exists($model, 'translations')) {
            return $base !== '' ? $base : $type->handle;
        }

        /** @var HasMany<Model, Model> $relation */
        $relation = $model->translations();
        $translationModel = $relation->getRelated();
        $foreignKey = $relation->getForeignKeyName();

        $base = $base !== '' ? $base : $type->handle;
        $slug = $base;
        $suffix = 2;

        while (
            $translationModel::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when(
                    $except !== null,
                    fn ($query) => $query->where($foreignKey, '!=', $except->getKey()),
                )
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function blueprint(ContentTypeDefinition $type): Blueprint
    {
        return $this->blueprints->find($type->blueprint);
    }

    /**
     * @return list<BlueprintField>
     */
    private function rootFields(Blueprint $blueprint, Model $model): array
    {
        $fillable = array_flip($model->getFillable());

        return array_values(array_filter(
            $blueprint->fields(),
            fn (BlueprintField $field): bool => ! $field->isLocalizable() && isset($fillable[$field->handle]),
        ));
    }

    /**
     * @return list<BlueprintField>
     */
    private function localizableFields(Blueprint $blueprint): array
    {
        return array_values(array_filter(
            $blueprint->fields(),
            fn (BlueprintField $field): bool => $field->isLocalizable(),
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePublicationIfNeeded(ContentTypeDefinition $type, array $data): array
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;
        $casts = (new $modelClass)->getCasts();

        if (($casts['status'] ?? null) !== ContentStatus::class || ! array_key_exists('status', $data)) {
            return $data;
        }

        return PublicationScheduler::normalize($data);
    }

    private function serializeFieldValue(BlueprintField $field, mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface && in_array($field->type, ['date', 'datetime'], true)) {
            $datetimeLocalHandles = [
                'published_at',
                'unpublished_at',
                'starts_at',
                'ends_at',
                'deadline_at',
                'occurred_at',
            ];

            return in_array($field->handle, $datetimeLocalHandles, true)
                ? $value->format('Y-m-d\TH:i')
                : $value->format('Y-m-d');
        }

        return $value;
    }

    private function castAttribute(Model $model, string $key, mixed $value): mixed
    {
        $cast = $model->getCasts()[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if ($cast === ContentStatus::class && is_string($value)) {
            return ContentStatus::from($value);
        }

        if ($cast === 'boolean') {
            return (bool) $value;
        }

        if (is_string($cast) && enum_exists($cast) && is_subclass_of($cast, BackedEnum::class) && is_string($value)) {
            return $cast::from($value);
        }

        if (in_array($cast, ['datetime', 'immutable_datetime', 'date', 'immutable_date'], true) && is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return $value;
    }

    private function saveRevisionIfSupported(Model $entry): void
    {
        if (method_exists($entry, 'saveRevision')) {
            $entry->saveRevision();
        }
    }
}
