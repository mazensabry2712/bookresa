<?php

namespace App\Http\Controllers\Platform;

use App\Support\AuditLogger;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformSettingsController
{
    public function index(PlatformSettings $settings): View
    {
        $values = $settings->all();

        return view('admin.settings.index', [
            'values' => [
                'platform_name' => $values['platform_name'] ?? config('bookresa.name', 'BookResa'),
                'support_email' => $values['support_email'] ?? '',
                'default_locale' => $values['default_locale'] ?? config('bookresa.default_locale', 'en'),
                'default_timezone' => $values['default_timezone'] ?? config('app.timezone', 'UTC'),
                'booking_slot_interval_minutes' => (int) ($values['booking_slot_interval_minutes'] ?? config('bookresa.booking.slot_interval_minutes', 15)),
                'centralized_booking_payments_enabled' => filter_var($values['centralized_booking_payments_enabled'] ?? false, FILTER_VALIDATE_BOOL),
                'commission_free_customer_limit' => (int) ($values['commission_free_customer_limit'] ?? 1000),
                'booking_commission_rate_percent' => number_format(((int) ($values['booking_commission_rate_basis_points'] ?? 500)) / 100, 2, '.', ''),
            ],
            'locales' => config('bookresa.locales', ['en', 'ar']),
        ]);
    }

    public function update(
        Request $request,
        PlatformSettings $settings,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:120'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'default_locale' => ['required', 'in:'.implode(',', config('bookresa.locales', ['en', 'ar']))],
            'default_timezone' => ['required', 'timezone'],
            'booking_slot_interval_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'commission_free_customer_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'booking_commission_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'centralized_booking_payments_enabled' => ['nullable', 'boolean'],
        ]);

        $centralizedPaymentsEnabled = $request->boolean('centralized_booking_payments_enabled');

        if ($centralizedPaymentsEnabled) {
            $requiredProviderSettings = [
                'Kashier platform merchant ID' => config('bookresa.payments.kashier.merchant_id'),
                'Kashier API key' => config('bookresa.payments.kashier.api_key'),
                'Kashier secret key' => config('bookresa.payments.kashier.secret_key'),
                'Kashier server webhook URL' => config('bookresa.payments.kashier.server_webhook'),
            ];
            $missingProviderSettings = array_keys(array_filter(
                $requiredProviderSettings,
                fn (mixed $value): bool => blank($value),
            ));

            if ($missingProviderSettings !== []) {
                return back()
                    ->withErrors([
                        'centralized_booking_payments_enabled' => __('Centralized payments cannot be enabled until the platform Kashier credentials and server webhook are configured.'),
                    ])
                    ->withInput();
            }
        }

        $rateBasisPoints = (int) round(((float) $validated['booking_commission_rate_percent']) * 100);
        $settings->put([
            'platform_name' => $validated['platform_name'],
            'support_email' => $validated['support_email'] ?? '',
            'default_locale' => $validated['default_locale'],
            'default_timezone' => $validated['default_timezone'],
            'booking_slot_interval_minutes' => (string) $validated['booking_slot_interval_minutes'],
            'centralized_booking_payments_enabled' => $centralizedPaymentsEnabled ? '1' : '0',
            'commission_free_customer_limit' => (string) (int) $validated['commission_free_customer_limit'],
            'booking_commission_rate_basis_points' => (string) $rateBasisPoints,
        ]);

        $auditLogger->log('Platform settings updated', null, [
            'keys' => array_keys($validated),
            'centralized_booking_payments_enabled' => $centralizedPaymentsEnabled,
            'commission_free_customer_limit' => (int) $validated['commission_free_customer_limit'],
            'booking_commission_rate_basis_points' => $rateBasisPoints,
        ]);

        return back()->with('status', __('Platform settings updated successfully.'));
    }
}
