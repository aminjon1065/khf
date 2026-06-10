<x-mail::message>
# {{ __('mail.subscription.heading') }}

{{ __('mail.subscription.greeting') }}

{{ __('mail.subscription.action_intro') }}

<x-mail::button :url="$confirmUrl">
{{ __('mail.subscription.action') }}
</x-mail::button>

{{ __('mail.subscription.ignore') }}

{{ __('mail.subscription.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
