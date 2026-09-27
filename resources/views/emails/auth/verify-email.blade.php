@php
    $isRtl = app()->getLocale() === 'ar';
    $userName = trim((string) ($user->name ?? ''));
    $greeting = $userName !== ''
        ? __('app.verify_email_greeting', ['name' => $userName])
        : __('app.verify_email_greeting_generic');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ __('app.verify_email_subject') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f4f6fb;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:620px;">
                    <tr>
                        <td style="padding:0 0 18px;text-align:{{ $isRtl ? 'right' : 'left' }};">
                            <div style="display:inline-block;font-size:25px;line-height:1;font-weight:800;letter-spacing:-0.6px;color:#1e2a44;">
                                Book<span style="color:#6366f1;">Resa</span>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:40px 36px;">
                            <div style="font-size:12px;line-height:18px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#6366f1;">
                                {{ __('app.verify_email_eyebrow') }}
                            </div>

                            <h1 style="margin:12px 0 0;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.6px;color:#111827;">
                                {{ __('app.verify_email_title') }}
                            </h1>

                            <p style="margin:18px 0 0;font-size:16px;line-height:28px;color:#475569;">
                                {{ $greeting }}
                            </p>

                            <p style="margin:8px 0 0;font-size:16px;line-height:28px;color:#475569;">
                                {{ __('app.verify_email_body') }}
                            </p>

                            <div style="margin:30px 0;">
                                <a href="{{ $url }}"
                                   style="display:inline-block;padding:13px 22px;border-radius:10px;background:#1e2a44;color:#ffffff;text-decoration:none;font-size:15px;line-height:22px;font-weight:700;">
                                    {{ __('app.verify_email_button') }}
                                </a>
                            </div>

                            <div style="padding:16px 18px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">
                                <p style="margin:0;font-size:13px;line-height:22px;color:#64748b;">
                                    {{ __('app.verify_email_link_help') }}
                                </p>
                                <p style="margin:10px 0 0;font-size:12px;line-height:20px;color:#475569;word-break:break-all;overflow-wrap:anywhere;">
                                    <a href="{{ $url }}" style="color:#6366f1;text-decoration:underline;">{{ $url }}</a>
                                </p>
                            </div>

                            <p style="margin:24px 0 0;font-size:13px;line-height:22px;color:#64748b;">
                                {{ __('app.verify_email_ignore') }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 8px 0;text-align:center;">
                            <p style="margin:0;font-size:12px;line-height:20px;color:#94a3b8;">
                                {{ __('app.verify_email_footer') }}
                            </p>
                            <p style="margin:6px 0 0;font-size:12px;line-height:20px;color:#94a3b8;">
                                © {{ now()->year }} BookResa
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
