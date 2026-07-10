<?php

namespace App\Http\Requests\Admin;

use App\Models\Subdivision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateSubdivisionRequest extends StoreSubdivisionRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $subdivision = $this->route('subdivision');
        $current = $subdivision instanceof Subdivision ? $subdivision->status : null;

        $rules = array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );

        if (($id = $this->currentSubdivisionId()) !== null) {
            $rules['parent_id'][] = Rule::notIn([$id]);
        }

        return $rules;
    }

    protected function currentSubdivisionId(): ?int
    {
        $subdivision = $this->route('subdivision');

        if ($subdivision instanceof Subdivision) {
            return $subdivision->id;
        }

        return is_numeric($subdivision) ? (int) $subdivision : null;
    }
}
