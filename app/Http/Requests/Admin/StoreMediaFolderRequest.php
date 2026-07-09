<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaContainer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'container' => [
                Rule::requiredIf($this->input('parent_id') === null),
                'nullable',
                Rule::in(MediaContainer::values()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
