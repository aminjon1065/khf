<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesAutosaveFromBlueprint;
use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AutosavePageRequest extends FormRequest
{
    use ValidatesAutosaveFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManagePages->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->autosaveRules();
    }

    protected function blueprintReference(): string
    {
        return 'page.default';
    }

    /**
     * @return array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>
     */
    protected function blueprintSlugConstraints(): array
    {
        return [
            'slug' => [
                'table' => 'page_translations',
                'column' => 'slug',
                'foreign_key' => 'page_id',
                'exclude_id' => $this->currentPageId(),
            ],
        ];
    }

    protected function currentPageId(): ?int
    {
        $page = $this->route('page');

        return $page instanceof Page ? $page->id : null;
    }
}
