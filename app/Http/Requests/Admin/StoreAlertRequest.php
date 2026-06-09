<?php

namespace App\Http\Requests\Admin;

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageAlerts->value) ?? false;
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
            'hazard_level' => ['required', Rule::in(HazardLevel::values())],
            'status' => ['required', Rule::in(AlertStatus::values())],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'is_dismissible' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.body"] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
