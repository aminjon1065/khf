<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionTopic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'subscriber_token' => ['nullable', 'string', 'min:32', 'max:64'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', Rule::in(SubscriptionTopic::values())],
            'region_id' => ['nullable', 'exists:regions,id'],
            'locale' => ['required', 'string', Rule::in(config('app.locales'))],
        ];
    }
}
