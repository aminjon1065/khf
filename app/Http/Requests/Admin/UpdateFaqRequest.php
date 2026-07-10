<?php

namespace App\Http\Requests\Admin;

use App\Models\Faq;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateFaqRequest extends StoreFaqRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $faq = $this->route('faq');
        $current = $faq instanceof Faq ? $faq->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }
}
