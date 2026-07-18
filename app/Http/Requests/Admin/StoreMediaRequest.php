<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Rules\SafeFileUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
            // Positive allowlist (images + office documents) validated against the file's real
            // content type, then the SafeFileUpload denylist as a second gate (ТЗ §12.4). SVG is
            // deliberately excluded — it is an XSS vector on the public media disk.
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/avif,application/pdf,'
                    .'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                    .'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                new SafeFileUpload,
            ],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ];
    }
}
