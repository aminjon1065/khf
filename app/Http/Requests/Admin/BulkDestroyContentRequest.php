<?php

namespace App\Http\Requests\Admin;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->contentType()->managePermission) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }

    public function contentType(): ContentTypeDefinition
    {
        /** @var string $handle */
        $handle = $this->route('type');

        return app(ContentTypeRegistry::class)->get($handle);
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var list<int> $ids */
        $ids = $this->validated('ids');

        return array_values(array_map(intval(...), $ids));
    }
}
