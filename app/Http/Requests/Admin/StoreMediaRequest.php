<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafeFileUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', new SafeFileUpload],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ];
    }
}
