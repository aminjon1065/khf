<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageDocuments->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request. File types are restricted to documents
     * and images — executables are rejected (ТЗ §12.4).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();

        $rules = [
            'type' => ['required', Rule::in(DocumentType::values())],
            'source' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,jpg,jpeg,png'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.name"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.description"] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
