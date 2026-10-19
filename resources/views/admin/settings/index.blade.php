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
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('All workspace booking payments use the platform payment account. Workspace owners do not configure separate merchant IDs.') }}</p>
                </div>

                <label class="flex items-start gap-3 rounded-xl br-surface-soft p-4 text-sm">
                    <input type="checkbox" name="centralized_booking_payments_enabled" value="1"
                           @checked(old('centralized_booking_payments_enabled', $values['centralized_booking_payments_enabled']))
                           class="mt-0.5 rounded border-slate-300">
                    <span>
                        <span class="block font-semibold">{{ __('Enable centralized electronic booking payments') }}</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('When enabled, customers must pay online for new bookings, using the platform Kashier account.') }}</span>
                    </span>
                </label>
                @error('centralized_booking_payments_enabled')
                    <p class="text-sm font-semibold text-rose-600">{{ $message }}</p>
                @enderror

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">{{ __('Free customer profiles before commission') }}</span>
                        <input type="number" name="commission_free_customer_limit" min="0" max="1000000" step="1"
                               value="{{ old('commission_free_customer_limit', $values['commission_free_customer_limit']) }}" required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('A workspace plan can override this fallback with its included customer limit.') }}</span>
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">{{ __('Booking commission rate (%)') }}</span>
                        <input type="number" name="booking_commission_rate_percent" min="0" max="100" step="0.01"
                               value="{{ old('booking_commission_rate_percent', $values['booking_commission_rate_percent']) }}" required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('Default 5%. Applies to paid bookings belonging to customer profiles after the included limit, not the first included customers.') }}</span>
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
