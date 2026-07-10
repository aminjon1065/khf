<?php

namespace App\Http\Requests\Admin;

use App\Models\GovService;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateGovServiceRequest extends StoreGovServiceRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $service = $this->route('govService');
        $current = $service instanceof GovService ? $service->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }
}
