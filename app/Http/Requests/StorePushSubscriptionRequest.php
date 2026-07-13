<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
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
            'endpoint' => ['required', 'url'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'subscriber_token' => ['required', 'string', 'max:255'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', 'max:64'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'locale' => ['required', 'string', 'max:5'],
        ];
    }
}
