<?php

namespace App\Http\Requests\Admin;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $type = $this->contentType();

        return $this->user()?->can($type->managePermission) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:json,csv,txt'],
            'update_existing' => ['boolean'],
        ];
    }

    public function contentType(): ContentTypeDefinition
    {
        /** @var string $handle */
        $handle = $this->route('type');

        return app(ContentTypeRegistry::class)->get($handle);
    }

    public function shouldUpdateExisting(): bool
    {
        return $this->boolean('update_existing');
    }
}
