<x-mail::message>
# {{ $title }}

**{{ __('mail.alert.level') }}:** {{ $levelLabel }}

@if ($body)
{{ $body }}
@endif

{{ __('mail.alert.auto_notice') }}

<x-mail::subcopy>
{{ __('mail.alert.unsubscribe', ['url' => $unsubscribeUrl]) }}
</x-mail::subcopy>
</x-mail::message>
