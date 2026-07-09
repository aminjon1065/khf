<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Rules\ValidBlueprintYaml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBlueprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageSettings->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $collection = (string) $this->route('collection');
        $name = (string) ($this->route('name') ?? 'default');
        $reference = "{$collection}.{$name}";

        return [
            'yaml' => ['required_without:schema', 'nullable', 'string', 'max:50000', new ValidBlueprintYaml($reference)],
            'schema' => ['required_without:yaml', 'nullable', 'array'],
            'schema.title' => ['required_with:schema', 'string', 'max:255'],
            'schema.sections' => ['required_with:schema', 'array', 'min:1'],
            'schema.sections.*.handle' => ['required_with:schema', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'schema.sections.*.display' => ['nullable', 'string', 'max:255'],
            'schema.sections.*.fields' => ['required_with:schema', 'array', 'min:1'],
            'schema.sections.*.fields.*.handle' => ['required_with:schema', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'schema.sections.*.fields.*.type' => ['required_with:schema', 'string', 'max:50'],
            'schema.sections.*.fields.*.display' => ['nullable', 'string', 'max:255'],
            'schema.sections.*.fields.*.instructions' => ['nullable', 'string', 'max:1000'],
            'schema.sections.*.fields.*.collection' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('schema') || ! is_array($this->input('schema'))) {
                return;
            }

            /** @var array<string, array{fields?: list<array{handle?: string}>}> $sections */
            $sections = $this->input('schema.sections', []);

            foreach ($sections as $sectionHandle => $section) {
                $handles = collect($section['fields'] ?? [])
                    ->pluck('handle')
                    ->filter(fn (mixed $handle): bool => is_string($handle) && $handle !== '')
                    ->all();

                if (count($handles) !== count(array_unique($handles))) {
                    $validator->errors()->add(
                        "schema.sections.{$sectionHandle}.fields",
                        'Handle полей в секции должны быть уникальными.',
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'yaml.required_without' => 'Укажите содержимое YAML-схемы.',
            'yaml.max' => 'YAML-схема слишком большая.',
            'schema.required_without' => 'Передайте схему конструктора или YAML.',
            'schema.title.required_with' => 'Укажите название blueprint.',
            'schema.sections.required_with' => 'Добавьте хотя бы одну секцию.',
            'schema.sections.*.fields.required_with' => 'В каждой секции должно быть хотя бы одно поле.',
            'schema.sections.*.fields.*.handle.regex' => 'Handle может содержать только латиницу, цифры и подчёркивание.',
        ];
    }
}
