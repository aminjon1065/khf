<?php

namespace App\Http\Requests\Admin;

use App\Models\Tender;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateTenderRequest extends StoreTenderRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tender = $this->route('tender');
        $current = $tender instanceof Tender ? $tender->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }

    protected function currentTenderId(): ?int
    {
        $tender = $this->route('tender');

        if ($tender instanceof Tender) {
            return $tender->id;
        }

        return is_numeric($tender) ? (int) $tender : null;
    }
}
