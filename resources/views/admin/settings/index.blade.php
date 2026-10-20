@extends('layouts.admin')

@section('title', __('Platform settings').' — BookResa')
@section('heading', __('Platform settings'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform operations') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Platform settings') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Manage global defaults and operational contact details for BookResa.') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.kashier.update') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            @csrf
            @method('PUT')

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold">{{ __('Kashier live payment integration') }}</h3>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ __('Manage production merchant credentials from Super Admin. Keys are encrypted in the database and are never shown again after saving.') }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">{{ __('Live mode') }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ __('Kashier API host') }}</span>
                    <input value="{{ $kashier['base_url'] }}" readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
                    <span class="mt-1 block text-xs text-slate-500">{{ __('The live API host is fixed for production credentials.') }}</span>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ __('Kashier Merchant ID') }}</span>
                    <input name="kashier_merchant_id" value="{{ old('kashier_merchant_id', $kashier['merchant_id']) }}" required maxlength="120" autocomplete="off"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    @error('kashier_merchant_id')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ __('Kashier live Payment API Key') }}</span>
                    <input type="password" name="kashier_api_key" value="" maxlength="1000" autocomplete="new-password"
                        placeholder="{{ $kashier['api_key_configured'] ? __('Saved — leave blank to keep the current key') : __('Enter the live Payment API Key') }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    <span class="mt-1 block text-xs {{ $kashier['api_key_configured'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $kashier['api_key_configured'] ? __('Payment API Key saved') : __('Payment API Key not configured') }}</span>
                    @error('kashier_api_key')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ __('Kashier live Secret Key') }}</span>
                    <input type="password" name="kashier_secret_key" value="" maxlength="2000" autocomplete="new-password"
                        placeholder="{{ $kashier['secret_key_configured'] ? __('Saved — leave blank to keep the current key') : __('Enter the live Secret Key') }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    <span class="mt-1 block text-xs {{ $kashier['secret_key_configured'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $kashier['secret_key_configured'] ? __('Secret Key saved') : __('Secret Key not configured') }}</span>
                    @error('kashier_secret_key')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ __('Server webhook URL') }}</span>
                    <input value="{{ $kashier['server_webhook'] }}" readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
                    <span class="mt-1 block text-xs text-slate-500">{{ __('Configure this exact HTTPS URL as an active live webhook in the Kashier dashboard.') }}</span>
                </label>
                <div class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Customer payment methods requested') }}</span>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">Visa / Mastercard · {{ __('Mobile wallets') }}</div>
                    <span class="mt-1 block text-xs text-slate-500">{{ __('Available methods depend on the live Kashier merchant account.') }}</span>
                </div>
            </div>

            <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">{{ __('Saving credentials does not enable customer charges by itself. Verify live merchant activation and webhook delivery, then enable booking payments separately below.') }}</p>

            <div class="flex justify-end">
                <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save Kashier credentials') }}</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Platform name') }}</span>
                    <input name="platform_name" value="{{ old('platform_name', $values['platform_name']) }}" required maxlength="120"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Support email') }}</span>
                    <input type="email" name="support_email" value="{{ old('support_email', $values['support_email']) }}" maxlength="255"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Default locale') }}</span>
                    <select name="default_locale" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                        @foreach ($locales as $locale)
                            <option value="{{ $locale }}" @selected(old('default_locale', $values['default_locale']) === $locale)>{{ strtoupper($locale) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Default timezone') }}</span>
                    <input name="default_timezone" value="{{ old('default_timezone', $values['default_timezone']) }}" required
                        placeholder="Africa/Cairo"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Booking slot interval (minutes)') }}</span>
                    <input type="number" name="booking_slot_interval_minutes" min="5" max="120" step="5"
                        value="{{ old('booking_slot_interval_minutes', $values['booking_slot_interval_minutes']) }}" required
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
            </div>

            <section class="space-y-4 rounded-xl border border-slate-200 p-5 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-bold">{{ __('Centralized booking payments & commission') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('Online payments use the platform payment account when enabled. The commission switch below does not enable or disable customer checkout.') }}</p>
                </div>

                <label class="flex items-start gap-3 rounded-xl br-surface-soft p-4 text-sm">
                    <input type="checkbox" name="centralized_booking_payments_enabled" value="1"
                           @checked(old('centralized_booking_payments_enabled', $values['centralized_booking_payments_enabled']))
                           class="mt-0.5 rounded border-slate-300">
                    <span>
                        <span class="block font-semibold">{{ __('Enable centralized electronic booking payments') }}</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('When enabled, customers may pay online in full through the platform Kashier account or choose cash at the center.') }}</span>
                    </span>
                </label>
                @error('centralized_booking_payments_enabled')
                    <p class="text-sm font-semibold text-rose-600">{{ $message }}</p>
                @enderror

                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 text-sm dark:border-slate-800">
                    <input type="checkbox" name="booking_commission_enabled" value="1"
                           @checked(old('booking_commission_enabled', $values['booking_commission_enabled']))
                           class="mt-0.5 rounded border-slate-300">
                    <span>
                        <span class="block font-semibold">{{ __('Enable booking commissions') }}</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('This policy is independent of online payments. Confirmed cash and online bookings use the same monthly allowance.') }}</span>
                    </span>
                </label>
                @error('booking_commission_enabled')
                    <p class="text-sm font-semibold text-rose-600">{{ $message }}</p>
                @enderror

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">{{ __('Free qualifying bookings per workspace each month') }}</span>
                        <input type="number" name="commission_free_booking_limit" min="0" max="1000000" step="1"
                               value="{{ old('commission_free_booking_limit', $values['commission_free_booking_limit']) }}" required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('Default 1,000. The count is per booking and resets at the start of each business-local calendar month.') }}</span>
                        @error('commission_free_booking_limit')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">{{ __('Booking commission rate (%)') }}</span>
                        <input type="number" name="booking_commission_rate_percent" min="0" max="100" step="0.01"
                               value="{{ old('booking_commission_rate_percent', $values['booking_commission_rate_percent']) }}" required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('Default 10%. Applies to eligible confirmed bookings after the free monthly allowance.') }}</span>
                        @error('booking_commission_rate_percent')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">{{ __('Do not enable live collection until the platform payment account, marketplace funds flow, workspace verification and payout process have been approved by the payment provider and reviewed for legal compliance.') }}</p>
            </section>

            <div class="flex justify-end">
                <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save settings') }}</button>
            </div>
        </form>
    </div>
@endsection
