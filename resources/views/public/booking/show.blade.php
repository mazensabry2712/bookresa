<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $profile = $tenant->profile;
        $locale = app()->getLocale();
        $businessName = $profile?->name[$locale] ?? $profile?->name['en'] ?? $tenant->slug;
        $businessDescription = $profile?->description[$locale] ?? $profile?->description['en'] ?? __('app.book_an_appointment_online');
        $baseBookingUrl = route('public.booking.show', $tenant->slug);
        $canonicalUrl = $baseBookingUrl.'?locale='.urlencode($locale);
        $alternates = collect(config('bookresa.locales', ['en', 'ar']))
            ->map(fn (string $alternateLocale): array => [
                'locale' => $alternateLocale,
                'url' => $baseBookingUrl.'?locale='.urlencode($alternateLocale),
            ])->values()->all();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $businessName,
            'description' => $businessDescription,
            'url' => $canonicalUrl,
            'potentialAction' => [
                '@type' => 'ReserveAction',
                'target' => $canonicalUrl,
            ],
        ];

        if ($profile?->phone) $jsonLd['telephone'] = $profile->phone;
        if ($profile?->email) $jsonLd['email'] = $profile->email;
        if ($profile?->address) {
            $jsonLd['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $profile->address];
        }

        $staffByService = $services->mapWithKeys(fn ($service) => [
            $service->id => $service->staff
                ->filter(fn ($staff) => $staff->status->value === 'active')
                ->map(fn ($staff) => ['id' => $staff->id, 'display_name' => $staff->display_name])
                ->values()->all(),
        ])->all();

        $bookingPayload = [
            'availabilityUrl' => route('public.booking.availability', $tenant->slug),
            'staffByService' => $staffByService,
        ];
    @endphp

    <x-seo
        :title="$businessName.' — '.config('bookresa.name', 'BookResa')"
        :description="$businessDescription"
        :canonical="$canonicalUrl"
        :alternates="$alternates"
        :json-ld="$jsonLd"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white">
    <div class="min-h-screen lg:grid lg:grid-cols-[minmax(18rem,0.7fr)_minmax(32rem,1fr)]">
        <aside class="hidden bg-brand-navy text-white lg:flex lg:flex-col">
            <div class="sticky top-0 flex min-h-screen flex-col p-8 xl:p-10">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-sm font-extrabold">B</span>
                    <span class="text-lg font-extrabold tracking-tight">BookResa</span>
                </a>

                <div class="mt-auto max-w-sm">
                    <div class="mb-5 h-1.5 w-16 rounded-full bg-brand-coral"></div>
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-indigo-200">{{ __('app.public_booking_ui.booking_page') }}</p>
                    <h1 class="mt-4 text-4xl font-extrabold leading-tight">{{ $businessName }}</h1>
                    <p class="mt-4 text-base leading-7 text-slate-300">{{ $businessDescription }}</p>

                    <div class="mt-8 space-y-3 text-sm text-slate-300">
                        @if ($profile?->location)<p><span class="font-semibold text-white">{{ __('app.public_booking_ui.location') }}</span> · {{ $profile->location }}</p>@endif
                        @if ($profile?->address)<p><span class="font-semibold text-white">{{ __('app.public_booking_ui.address') }}</span> · {{ $profile->address }}</p>@endif
                        @if ($profile?->phone)<p><span class="font-semibold text-white">{{ __('app.public_booking_ui.phone') }}</span> · {{ $profile->phone }}</p>@endif
                        @if ($profile?->email)<p><span class="font-semibold text-white">{{ __('app.public_booking_ui.email') }}</span> · {{ $profile->email }}</p>@endif
                    </div>
                </div>

                <div class="mt-10 flex items-center justify-between gap-3 text-xs text-slate-400">
                    <span>{{ __('app.public_booking_ui.powered_by') }}</span>
                    <a href="{{ route('home') }}" class="font-bold text-white hover:underline">BookResa</a>
                </div>
            </div>
        </aside>

        <main class="min-w-0">
            <div class="mx-auto max-w-3xl px-4 py-5 sm:px-6 sm:py-8 lg:px-10 xl:px-16">
                <header class="mb-6 flex items-center justify-between gap-3 lg:hidden">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-navy text-sm font-extrabold text-white">B</span>
                        <span class="text-sm font-extrabold">BookResa</span>
                    </a>
                    <div class="flex items-center gap-2">
                        <x-locale-switcher />
                        <x-theme-toggle />
                    </div>
                </header>

                <div class="mb-6 flex justify-end gap-2 max-lg:hidden">
                    <x-locale-switcher />
                    <x-theme-toggle />
                </div>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                        @if ($profile?->logo_path)
                            <img src="{{ Storage::disk('public')->url($profile->logo_path) }}" alt="{{ $businessName }}" class="h-16 w-16 rounded-2xl object-cover">
                        @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-xl font-extrabold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">{{ str($businessName)->substr(0, 1)->upper() }}</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.public_booking_ui.booking_page') }}</p>
                            <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('app.public_booking_ui.choose_time') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('app.public_booking_ui.choose_time_help') }}</p>
                        </div>
                    </div>

                    <div class="mt-7 grid grid-cols-4 gap-2" aria-label="{{ __('app.public_booking_ui.booking_steps') }}">
                        @foreach ([
                            ['number' => '1', 'label' => __('app.public_booking_ui.step_service')],
                            ['number' => '2', 'label' => __('app.public_booking_ui.step_time')],
                            ['number' => '3', 'label' => __('app.public_booking_ui.step_details')],
                            ['number' => '4', 'label' => __('app.public_booking_ui.step_confirm')],
                        ] as $step)
                            <div class="rounded-xl bg-slate-50 px-2.5 py-2.5 text-center dark:bg-slate-950/60">
                                <span class="block text-xs font-extrabold text-brand-indigo">{{ $step['number'] }}</span>
                                <span class="mt-0.5 block text-[10px] font-semibold text-slate-500 sm:text-xs">{{ $step['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                @if ($profile?->cover_path)
                    <div class="mt-5 overflow-hidden rounded-2xl">
                        <img src="{{ Storage::disk('public')->url($profile->cover_path) }}" alt="{{ $businessName }}" class="h-32 w-full object-cover sm:h-44">
                    </div>
                @endif

                <form method="POST" action="{{ route('public.booking.store', $tenant->slug) }}" class="mt-5 space-y-5" data-public-booking>
                    @csrf

                    @error('booking')
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200">{{ $message }}</div>
                    @enderror

                    @if ($errors->any() && ! $errors->has('booking'))
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200">{{ __('app.public_booking_ui.check_form') }}</div>
                    @endif

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.public_booking_ui.step_service') }}</p>
                                <h3 class="mt-1 text-lg font-bold">{{ __('app.public_booking_ui.select_service') }}</h3>
                            </div>
                            <span class="text-xs text-slate-400">{{ __('app.public_booking_ui.required') }}</span>
                        </div>

                        <label class="mt-5 block">
                            <span class="mb-1.5 block text-sm font-semibold">{{ __('app.service') }}</span>
                            <select id="service_id" name="service_id" required data-booking-service class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <option value="">{{ __('app.select_service') }}</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>
                                        {{ $service->name[$locale] ?? $service->name['en'] ?? __('app.service') }} — {{ number_format($service->price_minor / 100, 2) }} {{ $service->currency }} — {{ $service->duration_minutes }} {{ __('app.minutes_short') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </label>

                        @if ($services->isEmpty())
                            <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-center text-sm text-slate-500 dark:border-slate-700">{{ __('app.public_booking_ui.no_services') }}</div>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.public_booking_ui.step_time') }}</p>
                            <h3 class="mt-1 text-lg font-bold">{{ __('app.public_booking_ui.choose_time') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.public_booking_ui.availability_help') }}</p>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-[1fr_auto]">
                            <label>
                                <span class="mb-1.5 block text-sm font-semibold">{{ __('app.date') }}</span>
                                <input id="date" type="date" name="date" required min="{{ $today }}" value="{{ old('date') }}" data-booking-date
                                       class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @error('date')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </label>

                            <div data-booking-staff-wrap class="hidden sm:min-w-56">
                                <label>
                                    <span class="mb-1.5 block text-sm font-semibold">{{ __('app.staff_label') }}</span>
                                    <select name="staff_id" data-booking-staff class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 text-sm dark:border-slate-700 dark:bg-slate-950"></select>
                                </label>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold">{{ __('app.available_times') }}</p>
                                <span class="text-xs font-semibold text-slate-400" data-booking-slot-count></span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4" data-booking-slots></div>

                            <div class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-300" data-booking-loading>{{ __('app.loading_availability') }}</div>
                            <div class="mt-3 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200" data-booking-error>{{ __('app.public_booking_ui.availability_error') }}</div>

                            <input type="hidden" name="time" value="{{ old('time') }}" data-booking-time>
                            @error('time')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.public_booking_ui.step_details') }}</p>
                            <h3 class="mt-1 text-lg font-bold">{{ __('app.public_booking_ui.your_details') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.public_booking_ui.details_help') }}</p>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1.5 text-sm">
                                <span class="font-semibold">{{ __('app.name') }}</span>
                                <input name="name" value="{{ old('name') }}" required maxlength="160" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 dark:border-slate-700 dark:bg-slate-950">
                            </label>

                            <label class="space-y-1.5 text-sm">
                                <span class="font-semibold">{{ __('app.phone') }}</span>
                                <input name="phone" type="tel" value="{{ old('phone') }}" required inputmode="tel" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 dark:border-slate-700 dark:bg-slate-950">
                            </label>
                        </div>

                        <label class="mt-4 block">
                            <span class="mb-1.5 block text-sm font-semibold">{{ __('app.email') }} @unless ($customerEmailRequired)<span class="font-normal text-slate-400">({{ __('app.public_booking_ui.optional') }})</span>@endunless</span>
                            <input name="email" type="email" value="{{ old('email') }}" @required($customerEmailRequired) class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 dark:border-slate-700 dark:bg-slate-950">
                        </label>

                        <label class="mt-4 block">
                            <span class="mb-1.5 block text-sm font-semibold">{{ __('app.public_booking_ui.notes') }} <span class="font-normal text-slate-400">({{ __('app.public_booking_ui.optional') }})</span></span>
                            <textarea name="notes" rows="3" maxlength="2000" placeholder="{{ __('app.public_booking_ui.notes_placeholder') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 dark:border-slate-700 dark:bg-slate-950">{{ old('notes') }}</textarea>
                        </label>
                    </section>

                    <section class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-5 dark:border-indigo-900/60 dark:bg-indigo-950/25 sm:p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.public_booking_ui.payment_policy') }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">
                            @if ($paymentMode === 'full')
                                {{ __('app.public_booking_ui.full_payment_required') }}
                            @elseif ($paymentMode === 'deposit')
                                {{ __('app.public_booking_ui.deposit_required', ['percent' => $depositPercent]) }}
                            @else
                                {{ __('app.public_booking_ui.pay_later_policy') }}
                            @endif
                        </p>
                    </section>

                    <button type="submit" disabled data-booking-submit
                            class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-navy px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                        {{ $paymentMode === 'full' || $paymentMode === 'deposit' ? __('app.public_booking_ui.continue_to_payment') : __('app.public_booking_ui.confirm_booking') }}
                    </button>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const data = @js($bookingPayload);
                        const service = document.querySelector('[data-booking-service]');
                        const date = document.querySelector('[data-booking-date]');
                        const staffWrap = document.querySelector('[data-booking-staff-wrap]');
                        const staff = document.querySelector('[data-booking-staff]');
                        const slots = document.querySelector('[data-booking-slots]');
                        const loading = document.querySelector('[data-booking-loading]');
                        const error = document.querySelector('[data-booking-error]');
                        const time = document.querySelector('[data-booking-time]');
                        const submit = document.querySelector('[data-booking-submit]');
                        const count = document.querySelector('[data-booking-slot-count]');
                        let abortController = null;

                        if (!service || !date || !slots || !time || !submit) return;

                        const setState = (message) => {
                            slots.innerHTML = '';
                            const state = document.createElement('div');
                            state.className = 'col-span-full rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700';
                            state.textContent = message;
                            slots.appendChild(state);
                        };

                        const refreshSubmit = () => {
                            submit.disabled = !(service.value && date.value && time.value);
                        };

                        const refreshStaff = () => {
                            const list = data.staffByService?.[service.value] ?? [];
                            staff.innerHTML = '';

                            if (!list.length) {
                                staffWrap?.classList.add('hidden');
                                return;
                            }

                            staffWrap?.classList.remove('hidden');

                            const auto = document.createElement('option');
                            auto.value = '';
                            auto.textContent = @json(__('app.choose_automatically'));
                            staff.appendChild(auto);

                            list.forEach((member) => {
                                const option = document.createElement('option');
                                option.value = member.id;
                                option.textContent = member.display_name;
                                staff.appendChild(option);
                            });
                        };

                        const loadAvailability = async () => {
                            time.value = '';
                            refreshSubmit();
                            refreshStaff();
                            count.textContent = '';
                            error.classList.add('hidden');

                            if (!service.value || !date.value) {
                                loading.classList.add('hidden');
                                setState(@json(__('app.public_booking_ui.select_service_date')));
                                return;
                            }

                            if (abortController) abortController.abort();
                            abortController = new AbortController();

                            loading.classList.remove('hidden');
                            setState(@json(__('app.public_booking_ui.loading_slots')));

                            const params = new URLSearchParams({
                                service_id: service.value,
                                date: date.value,
                            });

                            if (staff.value) params.set('staff_id', staff.value);

                            try {
                                const response = await fetch(data.availabilityUrl + '?' + params.toString(), {
                                    headers: { 'Accept': 'application/json' },
                                    signal: abortController.signal,
                                });
                                const payload = await response.json();
                                if (!response.ok) throw new Error(payload.message || 'Availability error.');

                                const available = Array.isArray(payload.data) ? payload.data : [];
                                slots.innerHTML = '';
                                count.textContent = available.length ? String(available.length) : '';

                                if (!available.length) {
                                    setState(@json(__('app.no_available_times')));
                                    return;
                                }

                                available.forEach((slot) => {
                                    const button = document.createElement('button');
                                    button.type = 'button';
                                    button.className = 'min-h-11 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/30';
                                    button.dataset.bookingSlot = slot.time;
                                    button.textContent = slot.time;

                                    button.addEventListener('click', () => {
                                        slots.querySelectorAll('[data-booking-slot]').forEach((item) => {
                                            item.classList.remove('border-brand-indigo', 'bg-indigo-50', 'text-brand-indigo', 'dark:border-indigo-800', 'dark:bg-indigo-950/30', 'dark:text-indigo-300');
                                        });
                                        button.classList.add('border-brand-indigo', 'bg-indigo-50', 'text-brand-indigo', 'dark:border-indigo-800', 'dark:bg-indigo-950/30', 'dark:text-indigo-300');
                                        time.value = slot.time;
                                        refreshSubmit();
                                    });

                                    slots.appendChild(button);
                                });
                            } catch (requestError) {
                                if (requestError.name === 'AbortError') return;
                                error.classList.remove('hidden');
                                setState(@json(__('app.public_booking_ui.availability_error')));
                            } finally {
                                loading.classList.add('hidden');
                            }
                        };

                        service.addEventListener('change', loadAvailability);
                        date.addEventListener('change', loadAvailability);
                        staff?.addEventListener('change', loadAvailability);
                        refreshSubmit();

                        if (service.value && date.value) loadAvailability();
                        else setState(@json(__('app.public_booking_ui.select_service_date')));
                    });
                </script>
            </div>
        </main>
    </div>
</body>
</html>
