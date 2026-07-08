<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\PollType;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManagePolls->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();

        $rules = [
            'type' => ['required', Rule::in(PollType::values())],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'show_results' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'translations' => ['array'],
            'options' => ['required', 'array', 'min:2', 'max:20'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.sort_order' => ['integer', 'min:0', 'max:65535'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.description"] = ['nullable', 'string'];
            $rules["translations.{$locale}.slug"] = ['nullable', 'string', 'max:255'];
            $rules["options.*.translations.{$locale}.label"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
