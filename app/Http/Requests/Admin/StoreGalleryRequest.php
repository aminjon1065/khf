<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGalleryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageGallery->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request. Uploads are restricted to images
     * (ТЗ §12.4 secure uploads, §35 — GIF/JPEG/PNG formats).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $locales = Language::codes() ?: config('app.locales');
        $default = Language::defaultCode();
        $galleryId = $this->currentGalleryId();

        $rules = [
            'status' => ['required', Rule::in(ContentStatus::values())],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['integer'],
            'translations' => ['array'],
        ];

        foreach ($locales as $locale) {
            $rules["translations.{$locale}.title"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = [
                'nullable',
                "required_with:translations.{$locale}.title",
                'string',
                'max:255',
                'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u',
                Rule::unique('gallery_translations', 'slug')->where(function ($query) use ($locale, $galleryId) {
                    $query->where('locale', $locale);

                    if ($galleryId !== null) {
                        $query->where('gallery_id', '!=', $galleryId);
                    }
                }),
            ];
            $rules["translations.{$locale}.description"] = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }

    protected function currentGalleryId(): ?int
    {
        return null;
    }
}
