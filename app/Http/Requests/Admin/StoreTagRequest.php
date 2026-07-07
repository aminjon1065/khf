<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageTags->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();
        $tagId = $this->currentTagId();

        $rules = [
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.name"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = [
                'nullable',
                "required_with:translations.{$locale}.name",
                'string',
                'max:255',
                'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u',
                Rule::unique('tag_translations', 'slug')->where(function ($query) use ($locale, $tagId) {
                    $query->where('locale', $locale);

                    if ($tagId !== null) {
                        $query->where('tag_id', '!=', $tagId);
                    }
                }),
            ];
        }

        return $rules;
    }

    protected function currentTagId(): ?int
    {
        return null;
    }
}
