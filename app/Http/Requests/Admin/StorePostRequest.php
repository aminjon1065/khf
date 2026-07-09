<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\PostType;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    use ValidatesContentStatusTransition;
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManagePosts->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules(null),
        );
    }

    protected function blueprintReference(): string
    {
        return 'post.default';
    }

    /**
     * @return array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>
     */
    protected function blueprintSlugConstraints(): array
    {
        return [
            'slug' => [
                'table' => 'post_translations',
                'column' => 'slug',
                'foreign_key' => 'post_id',
                'exclude_id' => $this->currentPostId(),
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function blueprintSelectOptions(): array
    {
        return [
            'type' => PostType::values(),
        ];
    }

    protected function currentPostId(): ?int
    {
        return null;
    }
}
