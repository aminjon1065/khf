<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class BulkMoveMediaRequest extends BulkMediaRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);
    }
}
