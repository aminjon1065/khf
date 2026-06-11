<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageGuides->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();

        $rules = [
            'hazard_type' => ['nullable', Rule::in(IncidentType::values())],
            'audience' => ['required', Rule::in(GuideAudience::values())],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.summary"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$locale}.content"] = ['nullable', 'string'];
            $rules["translations.{$locale}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.seo_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }
}
