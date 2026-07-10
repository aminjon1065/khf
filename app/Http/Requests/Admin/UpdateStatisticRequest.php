<?php

namespace App\Http\Requests\Admin;

use App\Models\Statistic;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateStatisticRequest extends StoreStatisticRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $statistic = $this->route('statistic');
        $current = $statistic instanceof Statistic ? $statistic->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }
}
