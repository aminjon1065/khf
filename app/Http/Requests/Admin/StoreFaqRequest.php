<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFaqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageFaqs->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request. Only the default locale's question is
     * required; other locales are optional (ТЗ §14).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();

        $rules = [
            'status' => ['required', Rule::in(ContentStatus::values())],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.question"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:500'];
            $rules["translations.{$locale}.answer"] = ['nullable', 'string'];
        }

        return $rules;
    }
}
