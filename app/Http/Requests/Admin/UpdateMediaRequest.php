<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageMedia->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'translations' => ['sometimes', 'array'],
            'translations.*' => ['array'],
            'translations.*.alt_text' => ['nullable', 'string', 'max:500'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
            'media_folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'focal_x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'focal_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
