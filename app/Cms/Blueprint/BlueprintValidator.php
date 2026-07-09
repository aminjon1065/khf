<?php

namespace App\Cms\Blueprint;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Generates Laravel validation rules from a blueprint schema.
 */
class BlueprintValidator
{
    /**
     * @param  list<string>  $locales
     * @param  array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>  $slugConstraints
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(
        Blueprint $blueprint,
        array $locales,
        string $defaultLocale,
        array $slugConstraints = [],
        array $selectOptions = [],
    ): array {
        $rules = [];

        foreach ($blueprint->fields() as $field) {
            if ($field->isLocalizable()) {
                foreach ($locales as $locale) {
                    $key = "translations.{$locale}.{$field->handle}";
                    $rules[$key] = $this->fieldRules($field, $locale, $defaultLocale, $slugConstraints, $selectOptions);
                }

                continue;
            }

            $rules = array_merge($rules, $this->rootFieldRules($field, $selectOptions));
        }

        if ($blueprint->field('status') !== null) {
            $rules['translations'] = ['array'];
        }

        return $rules;
    }

    /**
     * Validation rules for non-localizable blueprint fields under a flat prefix (CMS globals).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function flatRules(Blueprint $blueprint, string $prefix = 'fields'): array
    {
        $rules = [$prefix => ['array']];

        foreach ($blueprint->fields() as $field) {
            if ($field->isLocalizable()) {
                continue;
            }

            $key = "{$prefix}.{$field->handle}";

            if (in_array($field->type, ['grid', 'replicator'], true)) {
                $rules = array_merge($rules, $this->nestedArrayRules($field, $key));

                continue;
            }

            $rules[$key] = match ($field->type) {
                'text', 'slug' => [
                    $field->isRequired() ? 'required' : 'nullable',
                    'string',
                    'max:'.($field->config['max'] ?? 500),
                ],
                'textarea' => [
                    $field->isRequired() ? 'required' : 'nullable',
                    'string',
                    'max:'.($field->config['max'] ?? 2000),
                ],
                default => ['nullable', 'string'],
            };
        }

        return $rules;
    }

    /**
     * @param  array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>  $slugConstraints
     * @param  array<string, list<string>>  $selectOptions
     * @return list<ValidationRule|string>
     */
    private function fieldRules(
        BlueprintField $field,
        string $locale,
        string $defaultLocale,
        array $slugConstraints,
        array $selectOptions,
    ): array {
        $isDefault = $locale === $defaultLocale;

        return match ($field->type) {
            'text', 'slug' => $this->textRules($field, $isDefault, $field->type === 'slug', $locale, $slugConstraints),
            'textarea' => [
                $field->isRequired() && $isDefault ? 'required' : 'nullable',
                'string',
                'max:'.($field->config['max'] ?? 500),
            ],
            'rich_text', 'bard' => ['nullable', 'string'],
            'blocks' => ['nullable', 'array'],
            default => ['nullable', 'string'],
        };
    }

    /**
     * @param  array<string, list<string>>  $selectOptions
     * @return array<string, list<ValidationRule|string>>
     */
    private function rootFieldRules(BlueprintField $field, array $selectOptions): array
    {
        return match ($field->type) {
            'select' => [
                $field->handle => array_filter([
                    $field->isRequired() ? 'required' : 'nullable',
                    'string',
                    isset($selectOptions[$field->handle])
                        ? Rule::in($selectOptions[$field->handle])
                        : null,
                ]),
            ],
            'entries' => $this->entriesRules($field),
            'date' => [
                $field->handle => $field->handle === 'unpublished_at'
                    ? ['nullable', 'date', 'after:published_at']
                    : ['nullable', 'date'],
            ],
            'assets' => [
                'cover' => ['nullable', 'image', 'max:5120'],
                'cover_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
                'remove_cover' => ['boolean'],
            ],
            'toggle' => [
                $field->handle => ['boolean'],
            ],
            'number' => [
                $field->handle => ['integer', 'min:0', 'max:65535'],
            ],
            'grid', 'replicator' => $this->nestedArrayRules($field, $field->handle),
            default => [],
        };
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    private function nestedArrayRules(BlueprintField $field, string $prefix): array
    {
        $rules = [
            $prefix => array_values(array_filter([
                $field->isRequired() ? 'required' : 'nullable',
                'array',
                $field->minItems() > 0 ? 'min:'.$field->minItems() : null,
                $field->maxItems() !== null ? 'max:'.$field->maxItems() : null,
            ])),
        ];

        foreach ($field->subFields() as $subField) {
            $rules["{$prefix}.*.{$subField->handle}"] = match ($subField->type) {
                'textarea' => ['nullable', 'string', 'max:'.($subField->config['max'] ?? 1000)],
                default => ['nullable', 'string', 'max:'.($subField->config['max'] ?? 500)],
            };
        }

        return $rules;
    }

    /**
     * @param  array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>  $slugConstraints
     * @return list<ValidationRule|string>
     */
    private function textRules(
        BlueprintField $field,
        bool $isDefault,
        bool $isSlug,
        string $locale,
        array $slugConstraints,
    ): array {
        $rules = [
            $field->isRequired() && $isDefault ? 'required' : 'nullable',
            'string',
            'max:'.($field->config['max'] ?? 255),
        ];

        if ($isSlug) {
            $rules = ['nullable', "required_with:translations.{$locale}.title", ...array_slice($rules, 1)];
            $rules[] = 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u';

            $constraint = $slugConstraints[$field->handle] ?? $slugConstraints['slug'] ?? null;

            if ($constraint !== null) {
                $rules[] = Rule::unique($constraint['table'], $constraint['column'])->where(function ($query) use ($locale, $constraint) {
                    $query->where('locale', $locale);

                    if ($constraint['exclude_id'] !== null) {
                        $query->where($constraint['foreign_key'], '!=', $constraint['exclude_id']);
                    }
                });
            }
        }

        return $rules;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    private function entriesRules(BlueprintField $field): array
    {
        $table = match ($field->collection()) {
            'categories' => 'categories',
            'tags' => 'tags',
            'pages' => 'pages',
            default => null,
        };

        if ($field->maxItems() === 1) {
            return [
                $field->handle => array_filter([
                    'nullable',
                    $table ? 'integer' : null,
                    $table ? "exists:{$table},id" : null,
                ]),
            ];
        }

        return [
            $field->handle => ['nullable', 'array'],
            "{$field->handle}.*" => array_filter([
                'integer',
                $table ? "exists:{$table},id" : null,
            ]),
        ];
    }
}
