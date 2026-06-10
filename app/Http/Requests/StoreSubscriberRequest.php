<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionTopic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriberRequest extends FormRequest
{
    /**
     * Public form — anyone may subscribe (rate-limited at the route).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'topics' => ['required', 'array', 'min:1'],
            'topics.*' => [Rule::in(SubscriptionTopic::values())],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            // Explicit consent to data processing + mailing (ТЗ §6.4.3, §12.5).
            'consent' => ['accepted'],
            // Honeypot anti-spam (ТЗ §12.4).
            'website' => ['prohibited'],
        ];
    }
}
