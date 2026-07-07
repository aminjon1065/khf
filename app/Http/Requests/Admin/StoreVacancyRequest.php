<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmploymentType;
use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVacancyRequest extends FormRequest
{
    use ValidatesContentStatusTransition;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageVacancies->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();
        $vacancyId = $this->currentVacancyId();

        $rules = [
            'employment_type' => ['required', Rule::in(EmploymentType::values())],
            'positions_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'published_at' => ['nullable', 'date'],
            'unpublished_at' => ['nullable', 'date', 'after:published_at'],
            'deadline_at' => ['nullable', 'date'],
            'translations' => ['array'],
        ];

        $rules = array_merge($rules, $this->statusTransitionRules(null));

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = [
                'nullable',
                "required_with:translations.{$locale}.title",
                'string',
                'max:255',
                'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u',
                Rule::unique('vacancy_translations', 'slug')->where(function ($query) use ($locale, $vacancyId) {
                    $query->where('locale', $locale);

                    if ($vacancyId !== null) {
                        $query->where('vacancy_id', '!=', $vacancyId);
                    }
                }),
            ];
            $rules["translations.{$locale}.department"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.location"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.salary"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.summary"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$locale}.description"] = ['nullable', 'string'];
            $rules["translations.{$locale}.requirements"] = ['nullable', 'string'];
            $rules["translations.{$locale}.responsibilities"] = ['nullable', 'string'];
            $rules["translations.{$locale}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.seo_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    protected function currentVacancyId(): ?int
    {
        return null;
    }
}
