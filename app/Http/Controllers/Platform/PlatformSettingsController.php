<?php

namespace App\Http\Controllers\Platform;

use App\Support\AuditLogger;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

final class PlatformSettingsController
{
    public function index(PlatformSettings $settings): View
    {
        $values = $settings->all();
        $storedKashier = $settings->storedKashierConfig();

        return view('admin.settings.index', [
            'values' => [
                'platform_name' => $values['platform_name'] ?? config('bookresa.name', 'BookResa'),
                'support_email' => $values['support_email'] ?? '',
                'default_locale' => $values['default_locale'] ?? config('bookresa.default_locale', 'en'),
                'default_timezone' => $values['default_timezone'] ?? config('app.timezone', 'UTC'),
                'booking_slot_interval_minutes' => (int) ($values['booking_slot_interval_minutes'] ?? config('bookresa.booking.slot_interval_minutes', 15)),
                'centralized_booking_payments_enabled' => filter_var($values['centralized_booking_payments_enabled'] ?? false, FILTER_VALIDATE_BOOL),
                'booking_commission_enabled' => filter_var($values['booking_commission_enabled'] ?? false, FILTER_VALIDATE_BOOL),
                'commission_free_booking_limit' => (int) ($values['commission_free_booking_limit'] ?? 1000),
                'booking_commission_rate_percent' => number_format(((int) ($values['monthly_booking_commission_rate_basis_points'] ?? 1000)) / 100, 2, '.', ''),
            ],
            'kashier' => [
                'mode' => 'live',
                'base_url' => 'https://api.kashier.io',
                'merchant_id' => $storedKashier['merchant_id'],
                'api_key_configured' => $storedKashier['api_key_configured'],
                'secret_key_configured' => $storedKashier['secret_key_configured'],
                'server_webhook' => $storedKashier['server_webhook'] !== ''
                    ? $storedKashier['server_webhook']
                    : route('webhooks.kashier'),
                'allowed_methods' => $storedKashier['allowed_methods'],
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
            'commission_free_booking_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'booking_commission_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'booking_commission_enabled' => ['nullable', 'boolean'],
            'centralized_booking_payments_enabled' => ['nullable', 'boolean'],
        ]);

        $centralizedPaymentsEnabled = $request->boolean('centralized_booking_payments_enabled');

        if ($centralizedPaymentsEnabled) {
            $kashier = $settings->storedKashierConfig();
            $requiredProviderSettings = [
                'Kashier live merchant ID' => $kashier['merchant_id'],
                'Kashier live Payment API Key' => $kashier['api_key'],
                'Kashier live Secret Key' => $kashier['secret_key'],
                'Kashier live server webhook URL' => $kashier['server_webhook'],
            ];
            $missingProviderSettings = array_keys(array_filter(
                $requiredProviderSettings,
                fn (mixed $value): bool => blank($value),
            ));

            if ($missingProviderSettings !== []) {
                return back()
                    ->withErrors([
                        'centralized_booking_payments_enabled' => __('Centralized payments cannot be enabled until the live Kashier credentials are saved in Super Admin Integrations.'),
                    ])
                    ->withInput($request->except(['kashier_api_key', 'kashier_secret_key']));
            }
        }

        $rateBasisPoints = (int) round(((float) $validated['booking_commission_rate_percent']) * 100);
        $commissionEnabled = $request->boolean('booking_commission_enabled');
        $settings->put([
            'platform_name' => $validated['platform_name'],
            'support_email' => $validated['support_email'] ?? '',
            'default_locale' => $validated['default_locale'],
            'default_timezone' => $validated['default_timezone'],
            'booking_slot_interval_minutes' => (string) $validated['booking_slot_interval_minutes'],
            'centralized_booking_payments_enabled' => $centralizedPaymentsEnabled ? '1' : '0',
            'booking_commission_enabled' => $commissionEnabled ? '1' : '0',
            'commission_free_booking_limit' => (string) (int) $validated['commission_free_booking_limit'],
            'monthly_booking_commission_rate_basis_points' => (string) $rateBasisPoints,
        ]);

        $auditLogger->log('Platform settings updated', null, [
            'keys' => array_keys($validated),
            'centralized_booking_payments_enabled' => $centralizedPaymentsEnabled,
            'booking_commission_enabled' => $commissionEnabled,
            'commission_free_booking_limit' => (int) $validated['commission_free_booking_limit'],
            'monthly_booking_commission_rate_basis_points' => $rateBasisPoints,
        ]);

        return back()->with('status', __('Platform settings updated successfully.'));
    }

    public function updateKashier(
        Request $request,
        PlatformSettings $settings,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        // Manual validation ensures failed requests never flash submitted secret values.
        $validator = Validator::make($request->all(), [
            'kashier_merchant_id' => ['required', 'string', 'max:120'],
            'kashier_api_key' => ['nullable', 'string', 'max:1000'],
            'kashier_secret_key' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except(['kashier_api_key', 'kashier_secret_key']));
        }

        $validated = $validator->validated();
        $current = $settings->storedKashierConfig();
        $apiKey = filled($validated['kashier_api_key'] ?? null)
            ? trim((string) $validated['kashier_api_key'])
            : $current['api_key'];
        $secretKey = filled($validated['kashier_secret_key'] ?? null)
            ? trim((string) $validated['kashier_secret_key'])
            : $current['secret_key'];

        $errors = [];

        if (blank($apiKey)) {
            $errors['kashier_api_key'] = __('Enter the live Kashier Payment API Key before saving this integration.');
        }

        if (blank($secretKey)) {
            $errors['kashier_secret_key'] = __('Enter the live Kashier Secret Key before saving this integration.');
        }

        if ($errors !== []) {
            return back()
                ->withErrors($errors)
                ->withInput($request->except(['kashier_api_key', 'kashier_secret_key']));
        }

        $settings->saveKashierCredentials([
            'merchant_id' => trim($validated['kashier_merchant_id']),
            'api_key' => filled($validated['kashier_api_key'] ?? null) ? (string) $validated['kashier_api_key'] : null,
            'secret_key' => filled($validated['kashier_secret_key'] ?? null) ? (string) $validated['kashier_secret_key'] : null,
            'server_webhook' => route('webhooks.kashier'),
        ]);

        $auditLogger->log('platform.integration.kashier_updated', null, [
            'mode' => 'live',
            'merchant_id' => trim($validated['kashier_merchant_id']),
            'api_key_rotated' => filled($validated['kashier_api_key'] ?? null),
            'secret_key_rotated' => filled($validated['kashier_secret_key'] ?? null),
            'webhook_url' => route('webhooks.kashier'),
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', __('Kashier live integration credentials saved securely.'));
    }
}
