<?php

namespace App\Http\Requests\Admin;

use App\Models\Leader;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateLeaderRequest extends StoreLeaderRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $leader = $this->route('leader');
        $current = $leader instanceof Leader ? $leader->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }
}
