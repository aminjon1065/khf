<?php

namespace App\Http\Requests\Admin;

use App\Models\Guide;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateGuideRequest extends StoreGuideRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $guide = $this->route('guide');
        $current = $guide instanceof Guide ? $guide->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }

    protected function currentGuideId(): ?int
    {
        $guide = $this->route('guide');

        if ($guide instanceof Guide) {
            return $guide->id;
        }

        return is_numeric($guide) ? (int) $guide : null;
    }
}
