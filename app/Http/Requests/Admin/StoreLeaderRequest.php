<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageLeadership->value) ?? false;
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
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['boolean'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $required = $locale === $default ? 'required' : 'nullable';
            $rules["translations.{$locale}.full_name"] = [$required, 'string', 'max:255'];
            $rules["translations.{$locale}.position"] = [$required, 'string', 'max:255'];
            $rules["translations.{$locale}.bio"] = ['nullable', 'string'];
            $rules["translations.{$locale}.reception"] = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }
}
