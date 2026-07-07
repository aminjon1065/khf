<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\PostType;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    use ValidatesContentStatusTransition;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManagePosts->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();
        $postId = $this->currentPostId();

        $rules = [
            'type' => ['required', Rule::in(PostType::values())],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'published_at' => ['nullable', 'date'],
            'unpublished_at' => ['nullable', 'date', 'after:published_at'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'remove_cover' => ['boolean'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'translations' => ['array'],
        ];

        $rules = array_merge($rules, $this->statusTransitionRules(null));

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = [
                'nullable',
                "required_with:translations.{$locale}.title",
                'string',
                'max:255',
                'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u',
                Rule::unique('post_translations', 'slug')->where(function ($query) use ($locale, $postId) {
                    $query->where('locale', $locale);

                    if ($postId !== null) {
                        $query->where('post_id', '!=', $postId);
                    }
                }),
            ];
            $rules["translations.{$locale}.excerpt"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$locale}.body"] = ['nullable', 'string'];
            $rules["translations.{$locale}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.seo_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    protected function currentPostId(): ?int
    {
        return null;
    }
}
