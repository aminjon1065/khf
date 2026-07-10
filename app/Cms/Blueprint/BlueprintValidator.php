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
            'assets' => $this->assetsRules($field),
            'toggle' => [
                $field->handle => ['boolean'],
            ],
            'text', 'textarea' => [
                $field->handle => array_values(array_filter([
                    $field->isRequired() ? 'required' : 'nullable',
                    $field->handle === 'email' ? 'email' : null,
                    $field->handle === 'external_url' ? 'url' : null,
                    'string',
                    'max:'.($field->config['max'] ?? ($field->type === 'textarea' ? 2000 : 255)),
                ])),
            ],
            'number' => $this->numberRules($field),
            'grid', 'replicator' => $this->nestedArrayRules($field, $field->handle),
            default => [],
        };
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    private function numberRules(BlueprintField $field): array
    {
        if ($field->handle === 'budget') {
            return [
                'budget' => ['nullable', 'numeric', 'min:0'],
            ];
        }

        if ($field->handle === 'latitude') {
            return [
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            ];
        }

        if ($field->handle === 'longitude') {
            return [
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ];
        }

        $min = (int) ($field->config['min'] ?? 0);
        $max = (int) ($field->config['max'] ?? 65535);

        return [
            $field->handle => array_values(array_filter([
                $field->isRequired() ? 'required' : 'nullable',
                'integer',
                "min:{$min}",
                "max:{$max}",
            ])),
        ];
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    private function assetsRules(BlueprintField $field): array
    {
        if ($field->handle === 'files') {
            $mimes = (string) ($field->config['mimes'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,jpg,jpeg,png');

            return [
                'files' => ['nullable', 'array'],
                'files.*' => ['file', 'max:20480', 'mimes:'.$mimes],
                'remove_files' => ['nullable', 'array'],
                'remove_files.*' => ['integer'],
            ];
        }

        if ($field->handle === 'photos') {
            $mimes = (string) ($field->config['mimes'] ?? 'jpg,jpeg,png,gif,webp');

            return [
                'photos' => ['nullable', 'array'],
                'photos.*' => ['image', 'mimes:'.$mimes, 'max:5120'],
                'remove_photos' => ['nullable', 'array'],
                'remove_photos.*' => ['integer'],
            ];
        }

        if ($field->handle === 'photo') {
            return [
                'photo' => ['nullable', 'image', 'max:5120'],
                'remove_photo' => ['boolean'],
            ];
        }

        return [
            'cover' => ['nullable', 'image', 'max:5120'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'remove_cover' => ['boolean'],
        ];
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
            $rules = [
                'nullable',
                'string',
                'max:'.($field->config['max'] ?? 255),
                'regex:/^$|^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u',
            ];

            if ($field->isRequired()) {
                array_unshift($rules, "required_with:translations.{$locale}.title");
            }

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
            'subdivisions' => 'subdivisions',
            'regions' => 'regions',
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
