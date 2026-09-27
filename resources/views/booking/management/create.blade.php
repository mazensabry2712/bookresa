@extends('layouts.dashboard')

@section('title', __('app.booking_ui.new_booking').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.booking_ui.new_booking'))

@section('content')
    @php
        $serviceName = static fn ($service): string => (string) (
            data_get($service?->name, app()->getLocale())
            ?? data_get($service?->name, 'en')
            ?? data_get($service?->name, 'ar')
            ?? '—'
        );

        $money = static fn (int $minor, string $currency): string => number_format($minor / 100, 2).' '.$currency;
        $today = now($timezone)->toDateString();
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-brand-indigo">{{ __('app.booking_ui.booking_management') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                    {{ __('app.booking_ui.new_booking') }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    {{ __('app.booking_ui.create_help') }}
                </p>
            </div>

            <a href="{{ route('booking.management.index') }}"
               class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                {{ __('app.booking_ui.back_to_bookings') }}
            </a>
        </section>

        <form method="POST" action="{{ route('booking.management.store') }}" class="space-y-6">
            @csrf

            <section class="br-panel p-5 sm:p-6">
                <div class="mb-5">
                    <h3 class="text-lg font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.appointment') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.create_appointment_help') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="md:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.service') }}</span>
                        <select name="service_id"
                                required
                                data-bookresa-booking-service
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                            <option value="">{{ __('app.booking_ui.select_service') }}</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}"
                                        data-staff-ids="{{ $service->staff->pluck('id')->join(',') }}"
                                        @selected((string) old('service_id') === (string) $service->id)>
                                    {{ $serviceName($service) }} — {{ $money((int) $service->price_minor, $service->currency) }} · {{ $service->duration_minutes }} {{ __('app.booking_ui.minutes') }}
                                </option>
                            @endforeach
                        </select>
                        <span class="mt-1.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.service_help') }}</span>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.date') }}</span>
                        <input type="date"
                               name="date"
                               value="{{ old('date', $today) }}"
                               min="{{ $today }}"
                               required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.time') }}</span>
                        <input type="time"
                               name="time"
                               value="{{ old('time') }}"
                               required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                    </label>

                    <label class="md:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.staff') }}</span>
                        <select name="staff_id"
                                data-bookresa-booking-staff
                                data-auto-label="{{ __('app.booking_ui.auto_assigned') }}"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                            <option value="">{{ __('app.booking_ui.auto_assigned') }}</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}"
                                        data-staff-option
                                        @selected((string) old('staff_id') === (string) $member->id)>
                                    {{ $member->display_name }}
                                </option>
                            @endforeach
                        </select>
                        <span data-bookresa-booking-staff-help class="mt-1.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.staff_help') }}</span>
                    </label>
                </div>
            </section>

            <section class="br-panel p-5 sm:p-6">
                <div class="mb-5">
                    <h3 class="text-lg font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.customer') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.customer_help') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="md:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.customer_name') }}</span>
                        <input name="name"
                               value="{{ old('name') }}"
                               autocomplete="name"
                               required
                               maxlength="160"
                               placeholder="{{ __('app.booking_ui.customer_name_placeholder') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.phone') }}</span>
                        <input name="phone"
                               value="{{ old('phone') }}"
                               autocomplete="tel"
                               maxlength="40"
                               placeholder="{{ __('app.booking_ui.phone_placeholder') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                    </label>

                    <label>
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.email') }}</span>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               autocomplete="email"
                               maxlength="255"
                               placeholder="{{ __('app.booking_ui.email_placeholder') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                    </label>

                    <label class="md:col-span-2">
                        <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.notes') }}</span>
                        <textarea name="notes"
                                  rows="4"
                                  maxlength="2000"
                                  placeholder="{{ __('app.booking_ui.notes_placeholder') }}"
                                  class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('booking.management.index') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('app.cancel') }}
                </a>
                <button type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                    {{ __('app.booking_ui.create_booking') }}
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const service = document.querySelector('[data-bookresa-booking-service]');
            const staff = document.querySelector('[data-bookresa-booking-staff]');
            const help = document.querySelector('[data-bookresa-booking-staff-help]');

            if (!service || !staff) {
                return;
            }

            const updateStaffOptions = () => {
                const option = service.options[service.selectedIndex];
                const ids = new Set((option?.dataset.staffIds ?? '').split(',').filter(Boolean));
                let visibleCount = 0;

                staff.querySelectorAll('[data-staff-option]').forEach((item) => {
                    const visible = ids.size === 0 || ids.has(item.value);
                    item.hidden = !visible;
                    if (visible) {
                        visibleCount++;
                    }
                });

                const current = staff.value;
                if (current && !ids.has(current) && ids.size > 0) {
                    staff.value = '';
                }

                if (help) {
                    help.textContent = ids.size === 0
                        ? @json(__('app.booking_ui.staff_auto_help'))
                        : @json(__('app.booking_ui.staff_select_help'));
                }

                staff.disabled = ids.size === 0;
                if (staff.disabled) {
                    staff.value = '';
                }
            };

            service.addEventListener('change', updateStaffOptions);
            updateStaffOptions();
        });
    </script>
@endsection
