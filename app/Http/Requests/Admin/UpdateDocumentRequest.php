<?php

namespace App\Http\Requests\Admin;

use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateDocumentRequest extends StoreDocumentRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $document = $this->route('document');
        $current = $document instanceof Document ? $document->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }
}
