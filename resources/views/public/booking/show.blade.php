<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $profile = $tenant->profile;
        $locale = app()->getLocale();
        $businessName = $profile?->name[$locale] ?? $profile?->name['en'] ?? $tenant->slug;
        $businessDescription = trim(strip_tags((string) ($profile?->description[$locale] ?? $profile?->description['en'] ?? '')));
        // Avoid exposing obviously corrupted one-token profile text in public metadata and copy.
        if (mb_strlen($businessDescription) > 45 && ! preg_match('/\s/u', $businessDescription)) {
            $businessDescription = __('app.book_an_appointment_online');
        }
        if ($businessDescription === '') {
            $businessDescription = __('app.book_an_appointment_online');
        }
        $businessDescription = str($businessDescription)->limit(180)->toString();
        $baseBookingUrl = route('public.booking.canonical.show', $tenant->slug);
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
        $staffRequiredByService = $services->mapWithKeys(fn ($service) => [
            $service->id => $service->staff->isNotEmpty(),
        ])->all();

        $bookingPayload = [
            'availabilityUrl' => route('public.booking.canonical.availability', $tenant->slug),
            'staffByService' => $staffByService,
            'staffRequiredByService' => $staffRequiredByService,
        ];

        $socialLinks = collect([
            'website' => __('app.business_ui.website'),
            'facebook' => __('app.business_ui.facebook'),
            'instagram' => __('app.business_ui.instagram'),
        ])->map(fn (string $label, string $key): array => [
            'label' => $label,
            'url' => data_get($profile?->social_links, $key),
        ])->filter(fn (array $link): bool => is_string($link['url'])
            && (str_starts_with($link['url'], 'https://') || str_starts_with($link['url'], 'http://')))
            ->values();

        $selectedPaymentMethod = (string) old(
            'payment_method',
            $paymentMethodMode === 'online_only' ? 'online' : 'cash',
        );
        if (! in_array($selectedPaymentMethod, $availablePaymentMethods, true)) {
            $selectedPaymentMethod = $availablePaymentMethods[0] ?? ($paymentMethodMode === 'online_only' ? 'online' : 'cash');
        }

        $hasCustomerErrors = $errors->has('name') || $errors->has('phone') || $errors->has('email')
            || $errors->has('notes') || $errors->has('payment_method') || old('name') !== null;
        $hasBookingSelection = old('service_id') || old('date') || old('time')
            || $errors->has('service_id') || $errors->has('date') || $errors->has('time') || $errors->has('booking');
        $initialStep = $hasCustomerErrors ? 3 : ($hasBookingSelection ? 2 : 1);
    @endphp

    <x-seo
        :title="$businessName.' — '.config('bookresa.name', 'BookResa')"
        :description="$businessDescription"
        :canonical="$canonicalUrl"
        :alternates="$alternates"
        :json-ld="$jsonLd"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Keep selected booking times visibly selected even if generated utility CSS is stale. */
        [data-booking-slots] button[data-booking-slot][aria-pressed="true"] {
            background-color: #eef2ff !important;
            border-color: #4f46e5 !important;
            color: #3730a3 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.24) !important;
            font-weight: 800 !important;
        }

        [data-booking-slots] button[data-booking-slot][aria-pressed="true"]::after {
            content: "✓";
            display: inline-block;
            margin-inline-start: 0.5rem;
            font-size: 1rem;
            font-weight: 900;
        }

        [data-booking-slots] button[data-booking-slot]:focus-visible {
            outline: 3px solid #818cf8;
            outline-offset: 2px;
        }

        .dark [data-booking-slots] button[data-booking-slot][aria-pressed="true"] {
            background-color: #312e81 !important;
            border-color: #818cf8 !important;
            color: #e0e7ff !important;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.28) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-white">
    <main class="mx-auto min-h-screen w-full max-w-5xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" aria-label="BookResa">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-navy text-base font-extrabold text-white shadow-sm">B</span>
                <span class="min-w-0">
                    <span class="block text-sm font-extrabold tracking-tight text-slate-900 dark:text-white">BookResa</span>
                    <span class="block text-xs text-slate-500">{{ __('app.public_booking_ui.booking_page') }}</span>
                </span>
            </a>
            <div class="flex items-center gap-2">
                <x-locale-switcher />
                <x-theme-toggle />
            </div>
        </header>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @if ($profile?->cover_path)
                <div class="h-28 overflow-hidden bg-slate-100 sm:h-40">
                    <img src="{{ Storage::disk('public')->url($profile->cover_path) }}" alt="" class="h-full w-full object-cover">
                </div>
            @endif

            <div class="p-5 sm:p-8 lg:p-10">
                <section class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center">
                    @if ($profile?->logo_path)
                        <img src="{{ Storage::disk('public')->url($profile->logo_path) }}" alt="{{ $businessName }}" class="h-16 w-16 shrink-0 rounded-2xl border border-slate-100 object-cover dark:border-slate-700">
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-2xl font-extrabold text-brand-indigo dark:bg-indigo-950/50">{{ str($businessName)->substr(0, 1)->upper() }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-indigo">{{ __('app.public_booking_ui.booking_page') }}</p>
                        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $businessName }}</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $businessDescription }}</p>
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            @if ($profile?->location)<span>{{ $profile->location }}</span>@endif
                            @if ($profile?->phone)<a href="tel:{{ $profile->phone }}" class="hover:text-brand-indigo">{{ $profile->phone }}</a>@endif
                            @if ($profile?->email)<a href="mailto:{{ $profile->email }}" class="hover:text-brand-indigo">{{ $profile->email }}</a>@endif
                        </div>
                    </div>
                </section>

                <div class="mb-8">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div class="min-w-0 max-w-2xl">
                            <h2 class="text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl dark:text-white">{{ __('app.public_booking_ui.choose_time') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.public_booking_ui.booking_intro_description') }}</p>
                        </div>
                        <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3.5 py-2 text-xs font-bold text-brand-indigo dark:border-indigo-900/70 dark:bg-indigo-950/40 dark:text-indigo-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                            <span data-step-counter aria-live="polite">{{ $locale === 'ar' ? 'الخطوة 1 من 3' : 'Step 1 of 3' }}</span>
                        </span>
                    </div>
                    <ol class="grid grid-cols-3 gap-2 sm:gap-3" aria-label="{{ __('app.public_booking_ui.booking_steps') }}">
                        @foreach ([
                            ['number' => 1, 'label' => __('app.public_booking_ui.step_service')],
                            ['number' => 2, 'label' => __('app.public_booking_ui.step_time')],
                            ['number' => 3, 'label' => __('app.public_booking_ui.step_details')],
                        ] as $step)
                            <li data-step-indicator="{{ $step['number'] }}" aria-current="{{ $step['number'] === $initialStep ? 'step' : 'false' }}" class="flex min-h-[76px] min-w-0 flex-col items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-2.5 py-3 text-center shadow-sm transition-all duration-200 sm:min-h-14 sm:flex-row sm:gap-3 sm:px-4 dark:border-slate-800 dark:bg-slate-900/60">
                                <span data-step-number class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-extrabold text-slate-500 transition-colors dark:bg-slate-800 dark:text-slate-300">{{ $step['number'] }}</span>
                                <span data-step-label class="block max-w-full text-[11px] font-bold leading-4 text-slate-500 transition-colors sm:text-sm dark:text-slate-400">{{ $step['label'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <form method="POST" action="{{ route('public.booking.canonical.store', $tenant->slug) }}" data-public-booking data-initial-step="{{ $initialStep }}" class="space-y-6">
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                            @error('booking')<p class="font-semibold">{{ $message }}</p>@enderror
                            @error('payment_method')<p class="font-semibold">{{ $message }}</p>@enderror
                            @if ($errors->has('service_id') || $errors->has('date') || $errors->has('time') || $errors->has('name') || $errors->has('phone') || $errors->has('email') || $errors->has('notes') || $errors->has('payment_method'))
                                <p>{{ __('app.public_booking_ui.check_form') }}</p>
                            @endif
                        </div>
                    @endif

                    <div data-wizard-error class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100" role="alert"></div>

                    <section data-step-panel="1" aria-labelledby="booking-step-service-title" class="space-y-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ $locale === 'ar' ? 'الخطوة الأولى' : 'STEP 1' }}</p>
                            <h3 id="booking-step-service-title" data-step-heading tabindex="-1" class="mt-1 text-xl font-extrabold">{{ __('app.public_booking_ui.select_service') }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $locale === 'ar' ? 'اختر الخدمة المناسبة لك لمعرفة السعر والمدة.' : 'Choose the service that fits your needs and see its price and duration.' }}</p>
                        </div>

                        @if ($services->isEmpty())
                            <div class="rounded-2xl border border-dashed border-slate-300 px-5 py-10 text-center dark:border-slate-700">
                                <p class="font-bold">{{ __('app.public_booking_ui.no_services') }}</p>
                            </div>
                        @else
                            <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('app.public_booking_ui.select_service') }}">
                                @foreach ($services as $service)
                                    @php
                                        $serviceName = $service->name[$locale] ?? $service->name['en'] ?? __('app.service');
                                        $serviceChecked = (string) old('service_id') === (string) $service->id;
                                    @endphp
                                    <label data-booking-service-card class="group relative flex min-h-28 cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 hover:bg-indigo-50/40 has-[:checked]:border-brand-indigo has-[:checked]:bg-indigo-50/70 has-[:checked]:ring-2 has-[:checked]:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950/30 dark:hover:bg-slate-900 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-950/30 dark:has-[:checked]:ring-indigo-950/60">
                                        <input type="radio" name="service_id" value="{{ $service->id }}" data-booking-service data-service-name="{{ $serviceName }}" data-service-price="{{ $service->price_minor / 100 }}" data-service-currency="{{ $service->currency }}" data-service-duration="{{ $service->duration_minutes }}" @checked($serviceChecked) required class="peer sr-only">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-white peer-checked:border-brand-indigo peer-checked:bg-brand-indigo dark:border-slate-600 dark:bg-slate-900">
                                            <span class="h-2 w-2 rounded-full bg-white opacity-0 peer-checked:opacity-100"></span>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-bold text-slate-900 dark:text-white">{{ $serviceName }}</span>
                                            <span class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                                                <span>{{ $service->duration_minutes }} {{ __('app.minutes_short') }}</span>
                                                <span aria-hidden="true">·</span>
                                                <span>{{ number_format($service->price_minor / 100, 2) }} {{ $service->currency }}</span>
                                            </span>
                                        </span>
                                        <span class="text-lg font-bold text-brand-indigo" aria-hidden="true">›</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section data-step-panel="2" aria-labelledby="booking-step-time-title" class="space-y-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ $locale === 'ar' ? 'الخطوة الثانية' : 'STEP 2' }}</p>
                            <h3 id="booking-step-time-title" data-step-heading tabindex="-1" class="mt-1 text-xl font-extrabold">{{ __('app.public_booking_ui.step_time') }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.public_booking_ui.availability_help') }}</p>
                        </div>

                        <div class="grid gap-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold">{{ __('app.date') }}</span>
                                <input id="booking-date" type="date" name="date" required min="{{ $today }}" value="{{ old('date') }}" data-booking-date class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-indigo focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950/60">
                                @error('date')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                            </label>

                            <div data-booking-staff-wrap class="hidden">
                                <label class="block">
                                    <span class="mb-2 block text-sm font-bold">{{ __('app.staff') }}</span>
                                    <select name="staff_id" data-booking-staff class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-indigo focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950/60"></select>
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/50 sm:p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h4 class="text-sm font-extrabold">{{ __('app.available_times') }}</h4>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400" data-booking-slot-count aria-live="polite" aria-atomic="true"></span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4" data-booking-slots role="group" aria-label="{{ __('app.available_times') }}">
                                <p class="col-span-full py-5 text-center text-sm text-slate-500">{{ __('app.public_booking_ui.select_service_date') }}</p>
                            </div>
                            <div data-booking-loading class="mt-3 hidden rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300" role="status" aria-live="polite">{{ __('app.loading_availability') }}</div>
                            <div data-booking-error class="mt-3 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200" role="alert">{{ __('app.public_booking_ui.availability_error') }}</div>
                            <input type="hidden" name="time" value="{{ old('time') }}" data-booking-time>
                            @error('time')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    <section data-step-panel="3" aria-labelledby="booking-step-details-title" class="space-y-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ $locale === 'ar' ? 'الخطوة الثالثة' : 'STEP 3' }}</p>
                            <h3 id="booking-step-details-title" data-step-heading tabindex="-1" class="mt-1 text-xl font-extrabold">{{ __('app.public_booking_ui.your_details') }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.public_booking_ui.details_help') }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold">{{ __('app.name') }}</span>
                                <input name="name" value="{{ old('name') }}" required maxlength="160" autocomplete="name" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-indigo focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950/60">
                                @error('name')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold">{{ __('app.phone') }}</span>
                                <input name="phone" type="tel" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-indigo focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950/60">
                                @error('phone')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        </div>

                        <label class="block">
                            <span class="mb-2 block text-sm font-bold">{{ __('app.email') }} <span data-email-optional class="font-normal text-slate-500 {{ $customerEmailRequired ? 'hidden' : '' }}">({{ __('app.public_booking_ui.optional') }})</span></span>
                            <input name="email" type="email" value="{{ old('email') }}" data-business-email-required="{{ $customerEmailRequired ? 'true' : 'false' }}" @required($customerEmailRequired) autocomplete="email" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-indigo focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950/60">
                            @error('email')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-bold">{{ __('app.public_booking_ui.notes') }} <span class="font-normal text-slate-500">({{ __('app.public_booking_ui.optional') }})</span></span>
                            <textarea name="notes" rows="3" maxlength="2000" placeholder="{{ __('app.public_booking_ui.notes_placeholder') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none transition focus:border-brand-indigo focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950/60">{{ old('notes') }}</textarea>
                            @error('notes')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/25">
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-brand-indigo dark:bg-slate-900" aria-hidden="true">✓</span>
                                <p class="font-extrabold">{{ $locale === 'ar' ? 'ملخص الحجز' : 'Booking summary' }}</p>
                            </div>
                            <dl class="space-y-3 text-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-600 dark:text-slate-300">{{ __('app.service') }}</dt>
                                    <dd class="text-end font-bold" data-summary-service>{{ $locale === 'ar' ? 'لم يتم الاختيار بعد' : 'Not selected yet' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-600 dark:text-slate-300">{{ __('app.date') }}</dt>
                                    <dd class="text-end font-bold" data-summary-date>—</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-600 dark:text-slate-300">{{ __('app.available_times') }}</dt>
                                    <dd class="text-end font-bold" data-summary-time>—</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-600 dark:text-slate-300">{{ __('app.public_booking_ui.payment_policy') }}</dt>
                                    <dd class="text-end font-bold" data-summary-payment-method>{{ $selectedPaymentMethod === 'online' ? __('app.public_booking_ui.pay_online_summary') : __('app.public_booking_ui.cash_at_center_summary') }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 border-t border-indigo-200 pt-3 dark:border-indigo-900/60">
                                    <dt class="font-extrabold">{{ $locale === 'ar' ? 'السعر' : 'Price' }}</dt>
                                    <dd class="text-end text-base font-extrabold text-brand-indigo" data-summary-price>—</dd>
                                </div>
                            </dl>
                        </div>

                        <fieldset class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800" data-payment-methods>
                            <legend class="px-1 text-sm font-extrabold">{{ __('app.public_booking_ui.payment_policy') }}</legend>
                            <p class="mb-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('app.public_booking_ui.choose_payment_method') }}</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @if ($cashPaymentsAvailable)
                                    <label class="group relative flex min-h-24 cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 hover:bg-indigo-50/40 has-[:checked]:border-brand-indigo has-[:checked]:bg-indigo-50/70 has-[:checked]:ring-2 has-[:checked]:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950/30 dark:hover:bg-slate-900 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-950/30">
                                        <input type="radio" name="payment_method" value="cash" data-payment-method data-payment-label="{{ __('app.public_booking_ui.cash_at_center_summary') }}" data-payment-help="{{ __('app.public_booking_ui.cash_at_center_help') }}" @checked($selectedPaymentMethod === 'cash') required class="peer sr-only">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-white peer-checked:border-brand-indigo peer-checked:bg-brand-indigo dark:border-slate-600 dark:bg-slate-900"><span class="h-2 w-2 rounded-full bg-white opacity-0 peer-checked:opacity-100"></span></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-bold text-slate-900 dark:text-white">{{ __('app.public_booking_ui.cash_at_center') }}</span>
                                            <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('app.public_booking_ui.cash_at_center_help') }}</span>
                                        </span>
                                    </label>
                                @endif
                                @if ($onlinePaymentsAvailable)
                                    <label class="group relative flex min-h-24 cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 hover:bg-indigo-50/40 has-[:checked]:border-brand-indigo has-[:checked]:bg-indigo-50/70 has-[:checked]:ring-2 has-[:checked]:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950/30 dark:hover:bg-slate-900 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-950/30">
                                        <input type="radio" name="payment_method" value="online" data-payment-method data-payment-label="{{ __('app.public_booking_ui.pay_online_summary') }}" data-payment-help="{{ __('app.public_booking_ui.pay_online_help') }}" @checked($selectedPaymentMethod === 'online') required class="peer sr-only">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-white peer-checked:border-brand-indigo peer-checked:bg-brand-indigo dark:border-slate-600 dark:bg-slate-900"><span class="h-2 w-2 rounded-full bg-white opacity-0 peer-checked:opacity-100"></span></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-bold text-slate-900 dark:text-white">{{ __('app.public_booking_ui.pay_online') }}</span>
                                            <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('app.public_booking_ui.pay_online_help') }}</span>
                                        </span>
                                    </label>
                                @elseif ($paymentMethodMode !== 'offline_only')
                                    <label class="group relative flex min-h-24 cursor-not-allowed items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70 dark:border-slate-700 dark:bg-slate-950/30">
                                        <input type="radio" name="payment_method" value="online" data-payment-method data-payment-label="{{ __('app.public_booking_ui.pay_online_summary') }}" data-payment-help="{{ __('app.public_booking_ui.online_payment_unavailable') }}" @checked($selectedPaymentMethod === 'online') disabled class="peer sr-only">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-white dark:border-slate-600 dark:bg-slate-900"></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-bold text-slate-900 dark:text-white">{{ __('app.public_booking_ui.pay_online') }}</span>
                                            <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('app.public_booking_ui.online_payment_unavailable') }}</span>
                                        </span>
                                    </label>
                                @endif
                            </div>
                            @if (count($availablePaymentMethods) === 0)
                                <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm leading-5 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">{{ __('app.public_booking_ui.online_only_temporarily_unavailable') }}</p>
                            @endif
                            <p data-payment-help class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm leading-5 text-slate-600 dark:bg-slate-950/60 dark:text-slate-300">{{ $selectedPaymentMethod === 'online' ? ($onlinePaymentsAvailable ? __('app.public_booking_ui.pay_online_help') : __('app.public_booking_ui.online_payment_unavailable')) : __('app.public_booking_ui.cash_at_center_help') }}</p>
                        </fieldset>
                    </section>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" data-wizard-back hidden class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                            {{ $locale === 'ar' ? 'رجوع' : 'Back' }}
                        </button>
                        <div class="flex flex-1 justify-end">
                            <button type="button" data-wizard-next hidden class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-slate-800 sm:w-auto dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                                {{ $locale === 'ar' ? 'التالي' : 'Continue' }} <span aria-hidden="true">→</span>
                            </button>
                            <button type="submit" data-booking-submit data-cash-label="{{ __('app.public_booking_ui.confirm_booking') }}" data-online-label="{{ __('app.public_booking_ui.continue_to_payment') }}" aria-busy="false" @disabled(count($availablePaymentMethods) === 0) class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                                {{ $selectedPaymentMethod === 'online' ? __('app.public_booking_ui.continue_to_payment') : __('app.public_booking_ui.confirm_booking') }}
                            </button>
                        </div>
                    </div>
                </form>

                <footer class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    <span>{{ __('app.public_booking_ui.powered_by') }} <a href="{{ route('home') }}" class="font-bold text-slate-700 hover:text-brand-indigo dark:text-slate-200">BookResa</a></span>
                    @if ($socialLinks->isNotEmpty())
                        <nav class="flex flex-wrap gap-x-4 gap-y-2" aria-label="{{ __('app.business_ui.public_presence') }}">
                            @foreach ($socialLinks as $socialLink)
                                <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold hover:text-brand-indigo">{{ $socialLink['label'] }}</a>
                            @endforeach
                        </nav>
                    @endif
                </footer>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-public-booking]');
            if (!form) return;

            const payload = @js($bookingPayload);
            const locale = document.documentElement.lang || 'en';
            const isArabic = locale.startsWith('ar');
            const messages = {
                chooseService: @js($locale === 'ar' ? 'اختر الخدمة أولًا للمتابعة.' : 'Please select a service to continue.'),
                chooseDate: @js($locale === 'ar' ? 'اختر التاريخ أولًا.' : 'Please choose a date first.'),
                chooseTime: @js($locale === 'ar' ? 'اختر أحد الأوقات المتاحة للمتابعة.' : 'Please choose an available time to continue.'),
                emptyAvailability: @js($locale === 'ar' ? 'لا توجد أوقات متاحة في هذا اليوم. جرّب تاريخًا آخر.' : 'No times are available on this date. Try another date.'),
                emptyStaffAvailability: @js(__('app.public_booking_ui.empty_staff_availability')),
                noActiveStaff: @js(__('app.public_booking_ui.no_active_staff_available')),
                chooseStaff: @js(__('app.public_booking_ui.select_staff_date')),
                chooseStaffOption: @js(__('app.public_booking_ui.select_staff')),
                serviceDate: @js(__('app.public_booking_ui.select_service_date')),
                loading: @js(__('app.public_booking_ui.loading_slots')),
                availabilityError: @js(__('app.public_booking_ui.availability_error')),
            };

            const panels = [...form.querySelectorAll('[data-step-panel]')];
            const indicators = [...document.querySelectorAll('[data-step-indicator]')];
            const counter = document.querySelector('[data-step-counter]');
            const wizardError = form.querySelector('[data-wizard-error]');
            const backButton = form.querySelector('[data-wizard-back]');
            const nextButton = form.querySelector('[data-wizard-next]');
            const submitButton = form.querySelector('[data-booking-submit]');
            const dateInput = form.querySelector('[data-booking-date]');
            const staffWrap = form.querySelector('[data-booking-staff-wrap]');
            const staffSelect = form.querySelector('[data-booking-staff]');
            const slotsContainer = form.querySelector('[data-booking-slots]');
            const loading = form.querySelector('[data-booking-loading]');
            const availabilityError = form.querySelector('[data-booking-error]');
            const slotCount = form.querySelector('[data-booking-slot-count]');
            const timeInput = form.querySelector('[data-booking-time]');
            const serviceInputs = [...form.querySelectorAll('[data-booking-service]')];
            const paymentMethodInputs = [...form.querySelectorAll('[data-payment-method]')];
            const emailInput = form.querySelector('input[name="email"]');
            const emailOptionalHint = form.querySelector('[data-email-optional]');
            const paymentHelp = form.querySelector('[data-payment-help]');
            const summaryService = form.querySelector('[data-summary-service]');
            const summaryDate = form.querySelector('[data-summary-date]');
            const summaryTime = form.querySelector('[data-summary-time]');
            const summaryPrice = form.querySelector('[data-summary-price]');
            const summaryPaymentMethod = form.querySelector('[data-summary-payment-method]');

            if (!panels.length || !dateInput || !staffSelect || !slotsContainer || !timeInput || !submitButton) return;

            let activeStep = Number(form.dataset.initialStep || 1);
            let abortController = null;
            let availabilitySequence = 0;
            const initialStaffId = @js((string) old('staff_id', ''));

            const checkedService = () => serviceInputs.find((input) => input.checked) || null;
            const checkedPaymentMethod = () => paymentMethodInputs.find((input) => input.checked) || null;
            const updatePaymentSelection = () => {
                const selected = checkedPaymentMethod();
                const isOnline = selected?.value === 'online';

                if (summaryPaymentMethod) {
                    summaryPaymentMethod.textContent = selected?.dataset.paymentLabel ?? '';
                }
                if (paymentHelp) {
                    paymentHelp.textContent = selected?.dataset.paymentHelp ?? '';
                }
                if (emailInput) {
                    const requiredByBusiness = emailInput.dataset.businessEmailRequired === 'true';
                    emailInput.required = requiredByBusiness || isOnline;
                    emailOptionalHint?.classList.toggle('hidden', emailInput.required);
                }
                submitButton.textContent = isOnline
                    ? submitButton.dataset.onlineLabel
                    : submitButton.dataset.cashLabel;
            };
            paymentMethodInputs.forEach((input) => input.addEventListener('change', updatePaymentSelection));

            const serviceRequiresStaff = () => {
                const selected = checkedService();
                return selected ? Boolean(payload.staffRequiredByService?.[selected.value]) : false;
            };
            const activeStaffForSelectedService = () => {
                const selected = checkedService();
                return selected ? (payload.staffByService?.[selected.value] || []) : [];
            };

            const clearWizardError = () => {
                wizardError.textContent = '';
                wizardError.classList.add('hidden');
            };

            const showWizardError = (message) => {
                wizardError.textContent = message;
                wizardError.classList.remove('hidden');
                wizardError.focus?.();
            };

            const setStep = (step, moveFocus = false) => {
                activeStep = Math.max(1, Math.min(3, Number(step)));
                panels.forEach((panel) => {
                    const current = Number(panel.dataset.stepPanel) === activeStep;
                    panel.classList.toggle('hidden', !current);
                    panel.setAttribute('aria-hidden', current ? 'false' : 'true');
                });

                indicators.forEach((indicator) => {
                    const number = Number(indicator.dataset.stepIndicator);
                    const current = number === activeStep;
                    const complete = number < activeStep;
                    indicator.setAttribute('aria-current', current ? 'step' : 'false');
                    indicator.classList.toggle('border-brand-indigo', current);
                    indicator.classList.toggle('bg-indigo-50', current || complete);
                    indicator.classList.toggle('dark:border-indigo-700', current);
                    indicator.classList.toggle('dark:bg-indigo-950/40', current || complete);
                    indicator.querySelector('[data-step-number]')?.classList.toggle('bg-brand-navy', current || complete);
                    indicator.querySelector('[data-step-number]')?.classList.toggle('text-white', current || complete);
                    indicator.querySelector('[data-step-number]')?.classList.toggle('dark:bg-white', current || complete);
                    indicator.querySelector('[data-step-number]')?.classList.toggle('dark:text-slate-900', current || complete);
                    indicator.querySelector('[data-step-label]')?.classList.toggle('text-brand-indigo', current || complete);
                    indicator.querySelector('[data-step-label]')?.classList.toggle('dark:text-indigo-300', current || complete);
                });

                if (counter) counter.textContent = isArabic ? 'الخطوة ' + activeStep + ' من 3' : 'Step ' + activeStep + ' of 3';
                backButton.hidden = activeStep === 1;
                nextButton.hidden = activeStep === 3;
                submitButton.hidden = activeStep !== 3;
                clearWizardError();
                refreshSummary();
                refreshSubmit();

                if (moveFocus) {
                    panels.find((panel) => Number(panel.dataset.stepPanel) === activeStep)
                        ?.querySelector('[data-step-heading]')?.focus();
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            };

            const setSlotsMessage = (message) => {
                slotsContainer.replaceChildren();
                const placeholder = document.createElement('p');
                placeholder.className = 'col-span-full py-5 text-center text-sm text-slate-500 dark:text-slate-400';
                placeholder.textContent = message;
                slotsContainer.appendChild(placeholder);
                if (slotCount) slotCount.textContent = '';
            };

            const formatPrice = (price, currency) => {
                const amount = Number(price);
                if (!Number.isFinite(amount)) return '—';
                try {
                    return new Intl.NumberFormat(locale, {
                        style: 'currency',
                        currency: currency || 'EGP',
                        maximumFractionDigits: 2,
                    }).format(amount);
                } catch {
                    return amount.toFixed(2) + ' ' + (currency || '');
                }
            };

            const refreshSummary = () => {
                const selected = checkedService();
                if (summaryService) {
                    summaryService.textContent = selected?.dataset.serviceName || (isArabic ? 'لم يتم الاختيار بعد' : 'Not selected yet');
                }
                if (summaryPrice) {
                    summaryPrice.textContent = selected
                        ? formatPrice(selected.dataset.servicePrice, selected.dataset.serviceCurrency)
                        : '—';
                }

                if (summaryDate) {
                    if (!dateInput.value) {
                        summaryDate.textContent = '—';
                    } else {
                        const date = new Date(dateInput.value + 'T12:00:00');
                        summaryDate.textContent = Number.isNaN(date.getTime())
                            ? dateInput.value
                            : new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(date);
                    }
                }
                if (summaryTime) summaryTime.textContent = timeInput.value || '—';
            };

            const refreshSubmit = () => {
                const hasAvailablePaymentMethod = paymentMethodInputs.some(
                    (input) => input.checked && !input.disabled,
                );
                const ready = Boolean(
                    checkedService()
                    && dateInput.value
                    && timeInput.value
                    && (!serviceRequiresStaff() || staffSelect.value)
                    && hasAvailablePaymentMethod
                );
                submitButton.disabled = !ready;
                submitButton.setAttribute('aria-busy', loading && !loading.classList.contains('hidden') ? 'true' : 'false');
            };

            const refreshStaff = (preferredId = '') => {
                const selected = checkedService();
                const staffMembers = activeStaffForSelectedService();
                const previousValue = preferredId || staffSelect.value || '';
                const shouldShowStaff = Boolean(selected && dateInput.value && serviceRequiresStaff());
                staffSelect.replaceChildren();
                staffSelect.required = false;

                if (!shouldShowStaff || !staffMembers.length) {
                    staffWrap?.classList.add('hidden');
                    staffSelect.value = '';
                    refreshSubmit();
                    return;
                }

                staffWrap?.classList.remove('hidden');
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = messages.chooseStaffOption;
                staffSelect.appendChild(placeholder);

                staffMembers.forEach((member) => {
                    const option = document.createElement('option');
                    option.value = String(member.id);
                    option.textContent = member.display_name;
                    staffSelect.appendChild(option);
                });

                if ([...staffSelect.options].some((option) => option.value === String(previousValue))) {
                    staffSelect.value = String(previousValue);
                }

                staffSelect.required = true;
                refreshSubmit();
            };

            const selectSlot = (button, slotTime) => {
                if (!button || !slotTime) return;

                slotsContainer.querySelectorAll('[data-booking-slot]').forEach((item) => {
                    item.classList.remove('border-brand-indigo', 'bg-indigo-50', 'text-brand-indigo', 'ring-2', 'ring-indigo-100', 'dark:border-indigo-700', 'dark:bg-indigo-950/50', 'dark:text-indigo-200');
                    item.setAttribute('aria-pressed', 'false');
                });
                button.classList.add('border-brand-indigo', 'bg-indigo-50', 'text-brand-indigo', 'ring-2', 'ring-indigo-100', 'dark:border-indigo-700', 'dark:bg-indigo-950/50', 'dark:text-indigo-200');
                button.setAttribute('aria-pressed', 'true');
                timeInput.value = slotTime;
                clearWizardError();
                refreshSummary();
                refreshSubmit();
            };

            const loadAvailability = async ({ preserveTime = false } = {}) => {
                const selected = checkedService();
                const desiredTime = preserveTime ? timeInput.value : '';
                timeInput.value = desiredTime;
                refreshSubmit();
                refreshSummary();
                if (availabilityError) availabilityError.classList.add('hidden');
                if (abortController) abortController.abort();
                const requestId = ++availabilitySequence;

                if (!selected || !dateInput.value) {
                    loading?.classList.add('hidden');
                    setSlotsMessage(messages.serviceDate);
                    timeInput.value = '';
                    refreshSubmit();
                    refreshSummary();
                    return;
                }

                const activeStaff = activeStaffForSelectedService();
                if (serviceRequiresStaff() && activeStaff.length === 0) {
                    loading?.classList.add('hidden');
                    setSlotsMessage(messages.noActiveStaff);
                    timeInput.value = '';
                    refreshSubmit();
                    refreshSummary();
                    return;
                }

                if (serviceRequiresStaff() && !staffSelect.value) {
                    loading?.classList.add('hidden');
                    setSlotsMessage(messages.chooseStaff);
                    timeInput.value = '';
                    refreshSubmit();
                    refreshSummary();
                    return;
                }

                abortController = new AbortController();
                loading?.classList.remove('hidden');
                if (loading) loading.textContent = messages.loading;
                setSlotsMessage(messages.loading);

                const query = new URLSearchParams({
                    service_id: selected.value,
                    date: dateInput.value,
                });
                if (staffSelect.value) query.set('staff_id', staffSelect.value);

                try {
                    const response = await fetch(payload.availabilityUrl + '?' + query.toString(), {
                        headers: { Accept: 'application/json' },
                        signal: abortController.signal,
                    });
                    const responsePayload = await response.json();
                    if (!response.ok) throw new Error(responsePayload.message || messages.availabilityError);

                    const available = Array.isArray(responsePayload.data) ? responsePayload.data : [];
                    slotsContainer.replaceChildren();
                    if (slotCount) slotCount.textContent = available.length ? String(available.length) : '';
                    let restored = false;

                    if (!available.length) {
                        setSlotsMessage(responsePayload.reason === 'no_active_staff'
                            ? messages.noActiveStaff
                            : (staffSelect.value ? messages.emptyStaffAvailability : messages.emptyAvailability));
                        timeInput.value = '';
                        return;
                    }

                    available.forEach((slot) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'min-h-12 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30 dark:focus:ring-indigo-950/60';
                        button.dataset.bookingSlot = slot.time;
                        button.textContent = slot.time;
                        button.setAttribute('aria-pressed', 'false');
                        button.addEventListener('click', () => selectSlot(button, slot.time));
                        slotsContainer.appendChild(button);

                        if (desiredTime && slot.time === desiredTime) {
                            selectSlot(button, slot.time);
                            restored = true;
                        }
                    });

                    if (desiredTime && !restored) timeInput.value = '';
                    refreshSummary();
                    refreshSubmit();
                } catch (requestError) {
                    if (requestError.name === 'AbortError') return;
                    if (requestId === availabilitySequence) {
                        if (availabilityError) {
                            availabilityError.textContent = messages.availabilityError;
                            availabilityError.classList.remove('hidden');
                        }
                        setSlotsMessage(messages.availabilityError);
                        timeInput.value = '';
                        refreshSummary();
                        refreshSubmit();
                    }
                } finally {
                    if (requestId === availabilitySequence) {
                        loading?.classList.add('hidden');
                        refreshSubmit();
                    }
                }
            };

            serviceInputs.forEach((input) => input.addEventListener('change', () => {
                timeInput.value = '';
                staffSelect.value = '';
                refreshStaff();
                refreshSummary();
                clearWizardError();
                loadAvailability();
            }));

            // Some browsers emit input before change for native date controls; debounce
            // both events to issue one availability request per actual date change.
            let dateAvailabilityTimer = null;
            let requestedDate = dateInput.value;
            const scheduleDateAvailability = () => {
                if (dateAvailabilityTimer !== null) {
                    window.clearTimeout(dateAvailabilityTimer);
                }

                dateAvailabilityTimer = window.setTimeout(() => {
                    dateAvailabilityTimer = null;
                    const nextDate = dateInput.value;
                    if (nextDate === requestedDate) return;

                    requestedDate = nextDate;
                    timeInput.value = '';
                    staffSelect.value = '';
                    refreshStaff();
                    refreshSummary();
                    clearWizardError();
                    loadAvailability();
                }, 0);
            };

            dateInput.addEventListener('input', scheduleDateAvailability);
            dateInput.addEventListener('change', scheduleDateAvailability);

            staffSelect.addEventListener('change', () => {
                timeInput.value = '';
                clearWizardError();
                loadAvailability();
            });

            nextButton.addEventListener('click', () => {
                clearWizardError();
                if (activeStep === 1 && !checkedService()) {
                    showWizardError(messages.chooseService);
                    return;
                }
                if (activeStep === 2) {
                    if (!dateInput.value) {
                        showWizardError(messages.chooseDate);
                        dateInput.reportValidity();
                        return;
                    }
                    if (serviceRequiresStaff() && activeStaffForSelectedService().length === 0) {
                        showWizardError(messages.noActiveStaff);
                        return;
                    }
                    if (serviceRequiresStaff() && !staffSelect.value) {
                        showWizardError(messages.chooseStaff);
                        staffSelect.focus();
                        return;
                    }
                    if (!timeInput.value) {
                        showWizardError(messages.chooseTime);
                        return;
                    }
                }
                setStep(activeStep + 1, true);
            });

            backButton.addEventListener('click', () => setStep(activeStep - 1, true));
            form.addEventListener('submit', (event) => {
                if (!checkedService()) {
                    event.preventDefault();
                    setStep(1, true);
                    showWizardError(messages.chooseService);
                    return;
                }
                if (!dateInput.value || !timeInput.value || (serviceRequiresStaff() && !staffSelect.value)) {
                    event.preventDefault();
                    setStep(2, true);
                    const message = !dateInput.value
                        ? messages.chooseDate
                        : (serviceRequiresStaff() && activeStaffForSelectedService().length === 0
                            ? messages.noActiveStaff
                            : (serviceRequiresStaff() && !staffSelect.value ? messages.chooseStaff : messages.chooseTime));
                    showWizardError(message);
                    return;
                }
                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
            });

            updatePaymentSelection();
            refreshStaff(initialStaffId);
            setStep(activeStep);
            if (checkedService() && dateInput.value) {
                loadAvailability({ preserveTime: Boolean(timeInput.value) });
            } else {
                setSlotsMessage(messages.serviceDate);
            }
        });
    </script>
</body>
</html>
