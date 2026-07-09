<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Support\RedirectResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRedirectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageSettings->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_path' => ['required', 'string', 'max:500', 'not_regex:/^https?:\/\//i', 'unique:redirects,from_path'],
            'to_url' => ['required', 'string', 'max:2000'],
            'status_code' => ['required', 'integer', Rule::in([301, 302])],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('from_path')) {
            $this->merge([
                'from_path' => RedirectResolver::normalizePath($this->string('from_path')->toString()),
            ]);
        }
    }
}
