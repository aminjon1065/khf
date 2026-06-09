<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageCategories->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request. Only the default locale's name is
     * required; other locales are optional (ТЗ §14).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();
        $categoryId = $this->currentCategoryId();

        $rules = [
            'sort_order' => ['integer', 'min:0', 'max:65535'],
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
                Rule::unique('category_translations', 'slug')->where(function ($query) use ($locale, $categoryId) {
                    $query->where('locale', $locale);

                    if ($categoryId !== null) {
                        $query->where('category_id', '!=', $categoryId);
                    }
                }),
            ];
        }

        return $rules;
    }

    protected function currentCategoryId(): ?int
    {
        return null;
    }
}
