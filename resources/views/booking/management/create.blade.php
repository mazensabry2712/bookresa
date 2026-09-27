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

    <div class="mx-auto max-w-6xl space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-sm font-semibold text-brand-indigo">
                    <a href="{{ route('booking.management.index') }}" class="hover:underline">{{ __('app.booking_ui.bookings') }}</a>
                    <span aria-hidden="true">/</span>
                    <span>{{ __('app.booking_ui.new_booking') }}</span>
                </div>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    {{ __('app.booking_ui.new_booking') }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    {{ __('app.booking_ui.create_help') }}
                </p>
            </div>

            <a href="{{ route('booking.management.index') }}"
               class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <span class="br-direction-arrow" aria-hidden="true">←</span>
                <span class="ms-2">{{ __('app.booking_ui.back_to_bookings') }}</span>
            </a>
        </section>

        <form method="POST"
              action="{{ route('booking.management.store') }}"
              class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]"
              data-internal-booking-form>
            @csrf

            <div class="min-w-0 space-y-6">
                @error('booking')
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200">{{ $message }}</div>
                @enderror

                @if ($errors->any() && ! $errors->has('booking'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200">{{ __('app.public_booking_ui.check_form') }}</div>
                @endif

                <section class="br-panel p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">01</p>
                            <h3 class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.appointment') }}</h3>
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.create_appointment_help') }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('app.required') }}</span>
                    </div>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <label class="md:col-span-2">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.service') }}</span>
                            <select name="service_id" required data-booking-service
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                                <option value="">{{ __('app.booking_ui.select_service') }}</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}"
                                            data-staff-ids="{{ $service->staff->pluck('id')->join(',') }}"
                                            data-service-name="{{ $serviceName($service) }}"
                                            data-duration="{{ $service->duration_minutes }}"
                                            data-price="{{ $money((int) $service->price_minor, $service->currency) }}"
                                            @selected((string) old('service_id') === (string) $service->id)>
                                        {{ $serviceName($service) }} — {{ $money((int) $service->price_minor, $service->currency) }} · {{ $service->duration_minutes }} {{ __('app.booking_ui.minutes') }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="mt-1.5 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.service_help') }}</span>
                            @error('service_id')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>

                        <label>
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.date') }}</span>
                            <input type="date" name="date" value="{{ old('date', $today) }}" min="{{ $today }}" required data-booking-date
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                            <span class="mt-1.5 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $timezone }}</span>
                            @error('date')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.choose_time') }}</span>
                                <span class="text-xs font-semibold text-slate-400" data-booking-slot-count></span>
                            </div>
                            <div class="mt-2 flex min-h-12 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 dark:border-slate-800 dark:bg-slate-950/60">
                                <span class="text-sm font-semibold text-slate-500 dark:text-slate-400" data-booking-selected-time>{{ old('time') ? __('app.booking_ui.selected_time').': '.old('time') : __('app.booking_ui.choose_date_first') }}</span>
                            </div>
                            <input type="hidden" name="time" value="{{ old('time') }}" data-booking-time>
                            @error('time')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.available_times') }}</span>
                                <span class="text-xs font-semibold text-slate-400" data-booking-loading-label></span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4" data-booking-slots></div>
                            <div class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-300" data-booking-state>{{ __('app.booking_ui.select_service_date') }}</div>
                            <div class="mt-3 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200" data-booking-error>{{ __('app.booking_ui.availability_error') }}</div>
                        </div>

                        <label class="md:col-span-2">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.staff') }}</span>
                            <select name="staff_id" data-bookresa-booking-staff data-auto-label="{{ __('app.booking_ui.auto_assigned') }}"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:disabled:bg-slate-900">
                                <option value="">{{ __('app.booking_ui.auto_assigned') }}</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}" data-staff-option @selected((string) old('staff_id') === (string) $member->id)>{{ $member->display_name }}</option>
                                @endforeach
                            </select>
                            <span data-bookresa-booking-staff-help class="mt-1.5 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.staff_help') }}</span>
                            @error('staff_id')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                    </div>
                </section>

                <section class="br-panel p-5 sm:p-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">02</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.customer') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.customer_help') }}</p>
                    </div>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <label class="md:col-span-2">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.existing_customer') }}</span>
                            <input list="booking-customer-list" type="text" autocomplete="off" placeholder="{{ __('app.booking_ui.existing_customer_placeholder') }}" data-booking-customer
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                            <datalist id="booking-customer-list">
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->name }}"
                                            data-phone="{{ $customer->phone }}"
                                            data-email="{{ $customer->email }}"
                                            data-customer-name="{{ $customer->name }}"></option>
                                @endforeach
                            </datalist>
                            <span class="mt-1.5 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('app.booking_ui.existing_customer_help') }}</span>
                        </label>

                        <label class="md:col-span-2">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.customer_name') }}</span>
                            <input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="160" placeholder="{{ __('app.booking_ui.customer_name_placeholder') }}" data-booking-customer-name
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                            @error('name')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>

                        <label>
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.phone') }}</span>
                            <input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" maxlength="40" placeholder="{{ __('app.booking_ui.phone_placeholder') }}" data-booking-customer-phone
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                            @error('phone')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>

                        <label>
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.email') }}</span>
                            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" placeholder="{{ __('app.booking_ui.email_placeholder') }}" data-booking-customer-email
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">
                            @error('email')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>

                        <label class="md:col-span-2">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('app.booking_ui.notes') }}</span>
                            <textarea name="notes" rows="4" maxlength="2000" placeholder="{{ __('app.booking_ui.notes_placeholder') }}"
                                      class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950">{{ old('notes') }}</textarea>
                            @error('notes')<p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('booking.management.index') }}"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.cancel') }}
                    </a>
                    <button type="submit" data-booking-submit disabled
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600 disabled:cursor-not-allowed disabled:opacity-50">
                        <span data-booking-submit-label>{{ __('app.booking_ui.create_booking') }}</span>
                    </button>
                </div>
            </div>

            <aside class="lg:sticky lg:top-6 lg:self-start">
                <section class="br-panel overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/60">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.booking_ui.summary') }}</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.booking_summary') }}</h3>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.booking_ui.summary_service') }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white" data-summary-service>—</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" data-summary-service-meta>—</p>
                        </div>
                        <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.booking_ui.summary_date') }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white" data-summary-date>—</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.booking_ui.summary_time') }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white" data-summary-time>—</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.booking_ui.summary_staff') }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white" data-summary-staff>{{ __('app.booking_ui.summary_auto') }}</p>
                        </div>
                        <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.booking_ui.summary_customer') }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white" data-summary-customer>—</p>
                        </div>
                        <div class="rounded-xl bg-indigo-50 px-3.5 py-3 dark:bg-indigo-950/30">
                            <p class="text-xs leading-5 text-indigo-700 dark:text-indigo-200">{{ __('app.booking_ui.summary_help') }}</p>
                        </div>
                    </div>
                </section>
            </aside>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-internal-booking-form]');
            if (!form) return;

            const service = form.querySelector('[data-booking-service]');
            const date = form.querySelector('[data-booking-date]');
            const staff = form.querySelector('[data-bookresa-booking-staff]');
            const staffHelp = form.querySelector('[data-bookresa-booking-staff-help]');
            const staffOptions = [...form.querySelectorAll('[data-staff-option]')];
            const slots = form.querySelector('[data-booking-slots]');
            const state = form.querySelector('[data-booking-state]');
            const error = form.querySelector('[data-booking-error]');
            const loadingLabel = form.querySelector('[data-booking-loading-label]');
            const time = form.querySelector('[data-booking-time]');
            const selectedTime = form.querySelector('[data-booking-selected-time]');
            const slotCount = form.querySelector('[data-booking-slot-count]');
            const submit = form.querySelector('[data-booking-submit]');
            const submitLabel = form.querySelector('[data-booking-submit-label]');
            const customerPicker = form.querySelector('[data-booking-customer]');
            const customerName = form.querySelector('[data-booking-customer-name]');
            const customerPhone = form.querySelector('[data-booking-customer-phone]');
            const customerEmail = form.querySelector('[data-booking-customer-email]');
            const customerOptions = [...document.querySelectorAll('#booking-customer-list option')];

            const summaryService = form.querySelector('[data-summary-service]');
            const summaryServiceMeta = form.querySelector('[data-summary-service-meta]');
            const summaryDate = form.querySelector('[data-summary-date]');
            const summaryTime = form.querySelector('[data-summary-time]');
            const summaryStaff = form.querySelector('[data-summary-staff]');
            const summaryCustomer = form.querySelector('[data-summary-customer]');

            const messages = {
                chooseServiceFirst: @json(__('app.booking_ui.choose_service_first')),
                chooseDateFirst: @json(__('app.booking_ui.choose_date_first')),
                loading: @json(__('app.booking_ui.loading_availability')),
                noTimes: @json(__('app.booking_ui.no_available_times')),
                available: @json(__('app.booking_ui.available_times')),
                autoAssigned: @json(__('app.booking_ui.auto_assigned')),
                staffAutoHelp: @json(__('app.booking_ui.staff_auto_help')),
                staffSelectHelp: @json(__('app.booking_ui.staff_select_help')),
                availabilityError: @json(__('app.booking_ui.availability_error')),
                creating: @json(__('app.booking_ui.creating_booking')),
                selectedTime: @json(__('app.booking_ui.selected_time')),
                minutes: @json(__('app.booking_ui.minutes')),
            };

            let requestController = null;

            const showState = (message) => {
                state.textContent = message;
                state.classList.remove('hidden');
            };

            const clearState = () => state.classList.add('hidden');

            const updateSummary = () => {
                const serviceOption = service.options[service.selectedIndex];
                const selectedCustomerOption = customerOptions.find((option) => option.value.toLowerCase() === (customerPicker?.value || '').trim().toLowerCase());

                summaryService.textContent = serviceOption?.dataset.serviceName || '—';

                const duration = serviceOption?.dataset.duration;
                const price = serviceOption?.dataset.price;
                summaryServiceMeta.textContent = duration && price
                    ? duration + ' ' + messages.minutes + ' · ' + price
                    : '—';

                summaryDate.textContent = date.value || '—';
                summaryTime.textContent = time.value || '—';

                const staffOption = staff.options[staff.selectedIndex];
                summaryStaff.textContent = staffOption?.textContent?.trim() || messages.autoAssigned;
                summaryCustomer.textContent = customerName?.value?.trim()
                    || selectedCustomerOption?.dataset.customerName
                    || '—';
            };

            const refreshSubmit = () => {
                submit.disabled = !service.value || !date.value || !time.value;
            };

            const updateStaffOptions = () => {
                const option = service.options[service.selectedIndex];
                const ids = new Set((option?.dataset.staffIds || '').split(',').filter(Boolean));

                staffOptions.forEach((item) => {
                    item.hidden = ids.size > 0 && !ids.has(item.value);
                });

                if (staff.value && ids.size > 0 && !ids.has(staff.value)) {
                    staff.value = '';
                }

                staff.disabled = ids.size === 0;
                staffHelp.textContent = ids.size === 0 ? messages.staffAutoHelp : messages.staffSelectHelp;
                updateSummary();
            };

            const renderSlots = (available) => {
                slots.innerHTML = '';

                if (!available.length) {
                    slotCount.textContent = '';
                    showState(messages.noTimes);
                    return;
                }

                clearState();
                slotCount.textContent = available.length + ' ' + messages.available;

                available.forEach((slot) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'min-h-11 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 focus-visible:ring-2 focus-visible:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/30';
                    button.dataset.bookingSlot = slot.time;
                    button.textContent = slot.time;

                    if (slot.time === time.value) {
                        button.classList.add('border-brand-indigo', 'bg-indigo-50', 'text-brand-indigo', 'dark:border-indigo-800', 'dark:bg-indigo-950/30', 'dark:text-indigo-300');
                    }

                    button.addEventListener('click', () => {
                        slots.querySelectorAll('[data-booking-slot]').forEach((item) => {
                            item.classList.remove('border-brand-indigo', 'bg-indigo-50', 'text-brand-indigo', 'dark:border-indigo-800', 'dark:bg-indigo-950/30', 'dark:text-indigo-300');
                        });

                        button.classList.add('border-brand-indigo', 'bg-indigo-50', 'text-brand-indigo', 'dark:border-indigo-800', 'dark:bg-indigo-950/30', 'dark:text-indigo-300');
                        time.value = slot.time;
                        selectedTime.textContent = messages.selectedTime + ': ' + slot.time;
                        refreshSubmit();
                        updateSummary();
                    });

                    slots.appendChild(button);
                });
            };

            const loadAvailability = async () => {
                time.value = '';
                selectedTime.textContent = date.value ? messages.chooseDateFirst : messages.chooseDateFirst;
                slots.innerHTML = '';
                slotCount.textContent = '';
                error.classList.add('hidden');
                refreshSubmit();
                updateSummary();

                if (!service.value) {
                    showState(messages.chooseServiceFirst);
                    return;
                }

                if (!date.value) {
                    showState(messages.chooseDateFirst);
                    return;
                }

                if (requestController) requestController.abort();
                requestController = new AbortController();
                clearState();
                loadingLabel.textContent = messages.loading;

                const params = new URLSearchParams({ service_id: service.value, date: date.value });
                if (staff.value) params.set('staff_id', staff.value);

                try {
                    const response = await fetch(@json(route('booking.management.availability')) + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: requestController.signal,
                    });

                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || messages.availabilityError);

                    renderSlots(Array.isArray(payload.data) ? payload.data : []);
                } catch (requestError) {
                    if (requestError.name === 'AbortError') return;
                    slots.innerHTML = '';
                    error.classList.remove('hidden');
                    showState(messages.availabilityError);
                } finally {
                    loadingLabel.textContent = '';
                }
            };

            customerPicker?.addEventListener('input', () => {
                const match = customerOptions.find((option) => option.value.toLowerCase() === customerPicker.value.trim().toLowerCase());

                if (match) {
                    customerName.value = match.dataset.customerName || match.value;
                    customerPhone.value = match.dataset.phone || '';
                    customerEmail.value = match.dataset.email || '';
                }

                updateSummary();
            });

            [customerName, customerPhone, customerEmail].forEach((field) => field?.addEventListener('input', updateSummary));

            service.addEventListener('change', () => {
                updateStaffOptions();
                loadAvailability();
            });

            date.addEventListener('change', loadAvailability);
            staff.addEventListener('change', loadAvailability);

            form.addEventListener('submit', () => {
                submit.disabled = true;
                submitLabel.textContent = messages.creating;
            });

            updateStaffOptions();
            updateSummary();
            refreshSubmit();

            if (service.value && date.value) loadAvailability();
            else showState(service.value ? messages.chooseDateFirst : messages.chooseServiceFirst);
        });
    </script>
@endsection
