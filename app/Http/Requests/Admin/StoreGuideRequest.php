<?php

namespace App\Http\Requests\Admin;

use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Enums\Permission;
use App\Http\Requests\Concerns\ValidatesContentStatusTransition;
use App\Http\Requests\Concerns\ValidatesFromBlueprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGuideRequest extends FormRequest
{
    use ValidatesContentStatusTransition;
    use ValidatesFromBlueprint;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageGuides->value) ?? false;
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
        return 'guide.default';
    }

    /**
     * @return array<string, array{table: string, column: string, foreign_key: string, exclude_id: int|null}>
     */
    protected function blueprintSlugConstraints(): array
    {
        return [
            'slug' => [
                'table' => 'guide_translations',
                'column' => 'slug',
                'foreign_key' => 'guide_id',
                'exclude_id' => $this->currentGuideId(),
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function blueprintSelectOptions(): array
    {
        return [
            'audience' => GuideAudience::values(),
            'hazard_type' => array_merge([''], IncidentType::values()),
        ];
    }

    protected function currentGuideId(): ?int
    {
        return null;
    }
}
