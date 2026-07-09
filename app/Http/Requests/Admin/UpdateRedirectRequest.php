<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Redirect;
use App\Support\RedirectResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRedirectRequest extends FormRequest
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
        /** @var Redirect $redirect */
        $redirect = $this->route('redirect');

        return [
            'from_path' => [
                'required',
                'string',
                'max:500',
                'not_regex:/^https?:\/\//i',
                Rule::unique('redirects', 'from_path')->ignore($redirect),
            ],
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
