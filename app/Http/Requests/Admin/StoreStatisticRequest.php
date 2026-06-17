<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStatisticRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageStatistics->value) ?? false;
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

        $rules = [
            'status' => ['required', Rule::in(ContentStatus::values())],
            'value' => ['required', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.label"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.unit"] = ['nullable', 'string', 'max:50'];
        }

        return $rules;
    }
}
