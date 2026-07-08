<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\ServiceCategory;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGovServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageServices->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();

        $rules = [
            'category' => ['required', Rule::in(ServiceCategory::values())],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'is_online' => ['boolean'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'processing_time' => ['nullable', 'string', 'max:255'],
            'fee' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.summary"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$locale}.description"] = ['nullable', 'string'];
            $rules["translations.{$locale}.eligibility"] = ['nullable', 'string'];
            $rules["translations.{$locale}.required_documents"] = ['nullable', 'string'];
            $rules["translations.{$locale}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.seo_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }
}
