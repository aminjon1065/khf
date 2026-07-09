<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesAutosaveFromBlueprint;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AutosavePostRequest extends FormRequest
{
    use ValidatesAutosaveFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManagePosts->value) ?? false;
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

    protected function currentPostId(): ?int
    {
        $post = $this->route('post');

        if ($post instanceof Post) {
            return $post->id;
        }

        return is_numeric($post) ? (int) $post : null;
    }
}
