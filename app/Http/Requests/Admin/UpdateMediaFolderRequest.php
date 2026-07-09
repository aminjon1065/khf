<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaContainer;
use App\Models\MediaFolder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMediaFolderRequest extends FormRequest
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $folder = $this->route('mediaFolder');

            if (! $folder instanceof MediaFolder) {
                return;
            }

            $parentId = $this->input('parent_id');

            if ($parentId === null) {
                return;
            }

            if ((int) $parentId === $folder->id) {
                $validator->errors()->add('parent_id', __('A folder cannot be its own parent.'));

                return;
            }

            if (in_array((int) $parentId, $folder->descendantIds(), true)) {
                $validator->errors()->add('parent_id', __('A folder cannot be moved inside its descendant.'));
            }
        });
    }
}
