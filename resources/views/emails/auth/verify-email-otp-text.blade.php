@php
    $userName = trim((string) ($user->name ?? ''));
    $greeting = $userName !== ''
        ? __('app.verify_email_greeting', ['name' => $userName])
        : __('app.verify_email_greeting_generic');
@endphp
{{ __('app.verify_email_subject') }}

{{ $greeting }}

{{ __('app.verify_email_body') }}

{{ __('app.verify_email_code_label') }}: {{ $code }}

{{ __('app.verify_email_code_expiry', ['minutes' => $expiresMinutes]) }}

{{ __('app.verify_email_ignore') }}

{{ __('app.verify_email_footer') }}

© {{ now()->year }} BookResa
