<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManagePages->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request. Translations are validated per active
     * locale; only the default locale is required, others may be filled later (ТЗ §14).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();
        $pageId = $this->currentPageId();

        $rules = [
            'status' => ['required', Rule::in(ContentStatus::values())],
            'parent_id' => ['nullable', 'integer', 'exists:pages,id'],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'is_home' => ['boolean'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = [
                'nullable',
                "required_with:translations.{$locale}.title",
                'string',
                'max:255',
                'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u',
                Rule::unique('page_translations', 'slug')->where(function ($query) use ($locale, $pageId) {
                    $query->where('locale', $locale);

                    if ($pageId !== null) {
                        $query->where('page_id', '!=', $pageId);
                    }
                }),
            ];
            $rules["translations.{$locale}.content"] = ['nullable', 'string'];
            $rules["translations.{$locale}.blocks"] = ['nullable', 'array'];
            $rules["translations.{$locale}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.seo_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * The page being updated (null when creating) — used to exclude its own slugs from the
     * per-locale uniqueness check.
     */
    protected function currentPageId(): ?int
    {
        return null;
    }
}
