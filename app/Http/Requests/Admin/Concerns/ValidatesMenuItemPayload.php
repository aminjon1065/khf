<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Language;
use Illuminate\Validation\Rule;

trait ValidatesMenuItemPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function menuItemRules(): array
    {
        $default = Language::defaultCode();
        $locales = Language::codes() ?: config('app.locales');

        $rules = [
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'url' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255', 'regex:/^(page\.\d+|entry\.[a-z_]+\.\d+|[a-z0-9_.-]+)$/i'],
            'target' => ['nullable', 'string', Rule::in(['_self', '_blank'])],
            'translations' => ['required', 'array'],
            "translations.{$default}.title" => ['required', 'string', 'max:255'],
        ];

        foreach ($locales as $locale) {
            if ($locale === $default) {
                continue;
            }

            $rules["translations.{$locale}.title"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
