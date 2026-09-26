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
        ]);

        $settings->put([
            'platform_name' => $validated['platform_name'],
            'support_email' => $validated['support_email'] ?? '',
            'default_locale' => $validated['default_locale'],
            'default_timezone' => $validated['default_timezone'],
            'booking_slot_interval_minutes' => (string) $validated['booking_slot_interval_minutes'],
        ]);

        $auditLogger->log('Platform settings updated', null, [
            'keys' => array_keys($validated),
        ]);

        return back()->with('status', __('Platform settings updated successfully.'));
    }
}
