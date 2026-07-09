<?php

namespace App\Http\Requests\Admin;

use App\Cms\Blueprint\BlueprintRepository;
use App\Cms\Blueprint\BlueprintValidator;
use App\Enums\Permission;
use App\Services\Cms\GlobalResolver;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageSettings->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $handle = (string) $this->route('handle');
        $definition = app(GlobalResolver::class)->definition($handle);

        if ($definition === null) {
            return [];
        }

        $blueprint = app(BlueprintRepository::class)->find($definition->blueprint);
        $rules = app(BlueprintValidator::class)->flatRules($blueprint);

        if ($handle === 'president') {
            $rules['fields.url'] = ['required', 'url', 'max:500'];
            $rules['fields.photo'] = ['required', 'string', 'max:500'];
        }

        if ($handle === 'social') {
            foreach (['telegram', 'facebook', 'instagram', 'youtube', 'x'] as $platform) {
                $rules["fields.{$platform}"] = ['nullable', 'string', 'max:500', 'url'];
            }
        }

        if ($handle === 'footer') {
            $rules['fields.government_url'] = ['nullable', 'string', 'max:500', 'url'];
            $rules['fields.egov_url'] = ['nullable', 'string', 'max:500', 'url'];
            $rules['fields.hotline'] = ['nullable', 'string', 'max:20', 'regex:/^[\d\s+\-()]+$/'];
            $rules['fields.copyright'] = ['nullable', 'string', 'max:1000'];
            $rules['fields.resource_links.*.url'] = ['nullable', 'string', 'max:500', 'url'];
        }

        if ($handle === 'seo_defaults') {
            $rules['fields.title'] = ['nullable', 'string', 'max:255'];
            $rules['fields.description'] = ['nullable', 'string', 'max:1000'];
            $rules['fields.image'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }
}
