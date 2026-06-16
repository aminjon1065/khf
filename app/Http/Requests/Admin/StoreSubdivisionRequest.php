<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubdivisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageStructure->value) ?? false;
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
        $subdivisionId = $this->currentSubdivisionId();

        $parentRules = ['nullable', 'integer', 'exists:subdivisions,id'];

        // A subdivision cannot be its own parent (ТЗ §20 «б» — coherent hierarchy).
        if ($subdivisionId !== null) {
            $parentRules[] = Rule::notIn([$subdivisionId]);
        }

        $rules = [
            'status' => ['required', Rule::in(ContentStatus::values())],
            'parent_id' => $parentRules,
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'staff_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.name"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.head"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.functions"] = ['nullable', 'string'];
            $rules["translations.{$locale}.address"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    protected function currentSubdivisionId(): ?int
    {
        return null;
    }
}
