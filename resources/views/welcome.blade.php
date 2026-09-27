@extends('layouts.public')

@section('content')
    @php
    $weekdayLabels = app()->getLocale() === 'ar'
        ? ['إ', 'ث', 'أ', 'خ', 'ج', 'س', 'ح']
        : ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
@endphp
    <main>
        <section class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                <div class="grid items-center gap-10 sm:gap-12 lg:grid-cols-2 lg:gap-14 xl:gap-16 2xl:gap-20">
                    <div class="min-w-0 max-w-xl">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.eyebrow') }}</p>
                        <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-[-0.045em] text-brand-navy dark:text-white sm:text-5xl lg:text-[3.25rem] xl:text-6xl">
                            {{ __('app.home_ui.hero_title') }}
                        </h1>
                        <p class="mt-6 max-w-lg text-base leading-7 text-slate-600 dark:text-slate-300 sm:text-lg sm:leading-8">
                            {{ __('app.home_ui.hero_description') }}
                        </p>

                        <div class="mt-8 flex flex-col items-stretch gap-3 min-[420px]:flex-row min-[420px]:items-center min-[420px]:gap-4">
                            <a href="{{ route('register') }}"
                               class="inline-flex min-h-11 w-full min-[420px]:flex-1 min-[420px]:w-auto items-center justify-center rounded-lg bg-brand-navy px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-slate-800 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                {{ __('app.home_ui.start_free') }}
                            </a>
                            <a href="#how-it-works"
                               class="inline-flex min-h-11 w-full min-[420px]:w-auto items-center justify-center px-1 py-3 text-sm font-bold text-slate-700 transition-colors hover:text-brand-navy dark:text-slate-200 dark:hover:text-white">
                                {{ __('app.home_ui.explore_features') }}
                            </a>
                        </div>

                        <div class="mt-8 grid gap-3 sm:grid-cols-3">
                            @foreach ([
                                [__('app.home_ui.proof_bookings'), __('app.home_ui.proof_bookings_text')],
                                [__('app.home_ui.proof_team'), __('app.home_ui.proof_team_text')],
                                [__('app.home_ui.proof_growth'), __('app.home_ui.proof_growth_text')],
                            ] as $proof)
                                <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-3 dark:border-slate-800 dark:bg-slate-900/70">
                                    <p class="text-xs font-extrabold text-slate-900 dark:text-white">{{ $proof[0] }}</p>
                                    <p class="mt-1 text-[11px] leading-5 text-slate-500 dark:text-slate-400">{{ $proof[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5 dark:border-slate-800">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="relative flex h-8 w-[90px] shrink-0 items-center overflow-hidden rounded-md sm:h-9 sm:w-[105px]">
                                        <img src="{{ asset('logo.png') }}" alt="BookResa" width="707" height="353" loading="lazy" decoding="async" class="absolute inset-x-0 top-1/2 h-auto w-full max-w-none -translate-y-1/2 dark:hidden">
                                        <img src="{{ asset('logodark.png') }}" alt="BookResa" width="707" height="353" loading="lazy" decoding="async" class="absolute inset-x-0 top-1/2 hidden h-auto w-full max-w-none -translate-y-1/2 dark:block">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ __('app.home_ui.preview_business') }}</p>
                                        <p class="truncate text-xs text-slate-400">{{ __('app.home_ui.preview_label') }}</p>
                                    </div>
                                </div>
                                <span class="hidden max-w-[8rem] truncate text-xs font-semibold text-slate-400 min-[420px]:block">{{ __('app.home_ui.preview_timezone') }}</span>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="flex items-end justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.home_ui.preview_service_label') }}</p>
                                        <p class="mt-2 truncate text-base font-extrabold text-slate-900 dark:text-white">{{ __('app.home_ui.preview_service_1') }}</p>
                                        <p class="mt-1 truncate text-xs text-slate-400">{{ __('app.home_ui.preview_service_meta') }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-extrabold text-brand-indigo">{{ __('app.home_ui.preview_price') }}</span>
                                </div>

                                <div class="mt-6 border-y border-slate-100 py-5 dark:border-slate-800">
                                    <div class="grid grid-cols-7 gap-0.5 text-center text-[8px] font-bold uppercase text-slate-400 min-[380px]:gap-1 min-[380px]:text-[9px]">
                                        @foreach ($weekdayLabels as $weekday)
                                            <span>{{ $weekday }}</span>
                                        @endforeach
                                    </div>
                                    <div class="mt-2 grid grid-cols-7 gap-0.5 min-[380px]:gap-1">
                                        @foreach (['21','22','23','24','25','26','27','28','29','30','01','02','03','04','05','06','07','08','09','10','11'] as $day)
                                            <span class="{{ $day === '01' ? 'bg-brand-indigo text-white' : 'text-slate-600 dark:text-slate-300' }} rounded-md px-1 py-1.5 text-center text-[11px] font-bold">
                                                {{ $day }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="truncate text-xs font-semibold text-slate-400">{{ __('app.home_ui.preview_time_label') }}</p>
                                        <span class="shrink-0 text-xs font-semibold text-slate-400">{{ __('app.home_ui.preview_timezone') }}</span>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-2 min-[400px]:grid-cols-3">
                                        @foreach (['09:00', '10:30', '11:30', '13:00', '14:00', '15:30'] as $time)
                                            <span class="{{ $time === '11:30' ? 'border-brand-indigo bg-brand-indigo text-white' : 'border-slate-200 text-slate-700 dark:border-slate-800 dark:text-slate-200' }} rounded-lg border px-2.5 py-2.5 text-center text-sm font-bold sm:px-3">
                                                {{ $time }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.workflow_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-brand-navy dark:text-white sm:text-4xl">
                        {{ __('app.home_ui.workflow_title') }}
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.home_ui.workflow_description') }}
                    </p>
                </div>

                <div class="mt-10 grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-800 dark:bg-slate-800">
                    @foreach ([
                        [1, __('app.home_ui.step_1_title'), __('app.home_ui.step_1_text')],
                        [2, __('app.home_ui.step_2_title'), __('app.home_ui.step_2_text')],
                        [3, __('app.home_ui.step_3_title'), __('app.home_ui.step_3_text')],
                        [4, __('app.home_ui.step_4_title'), __('app.home_ui.step_4_text')],
                    ] as $step)
                        <article class="bg-white px-5 py-6 dark:bg-slate-900">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-navy text-xs font-extrabold text-white dark:bg-indigo-500">
                                {{ str_pad((string) $step[0], 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <h3 class="mt-5 text-sm font-extrabold text-slate-900 dark:text-white">{{ $step[1] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $step[2] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid items-center gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
                    <div class="min-w-0 max-w-xl">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.product_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-brand-navy dark:text-white sm:text-4xl">
                            {{ __('app.home_ui.product_title') }}
                        </h2>
                        <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                            {{ __('app.home_ui.product_description') }}
                        </p>

                        <div class="mt-7 space-y-4">
                            @foreach ([
                                [__('app.home_ui.feature_bookings_title'), __('app.home_ui.feature_bookings_text')],
                                [__('app.home_ui.feature_calendar_title'), __('app.home_ui.feature_calendar_text')],
                                [__('app.home_ui.feature_services_title'), __('app.home_ui.feature_services_text')],
                                [__('app.home_ui.feature_customers_title'), __('app.home_ui.feature_customers_text')],
                            ] as $point)
                                <div class="flex gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-indigo/10 text-[10px] font-extrabold text-brand-indigo dark:bg-indigo-400/10 dark:text-indigo-300">✓</span>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $point[0] }}</h3>
                                        <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $point[1] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-4 sm:px-5 dark:border-slate-800">
                                <div class="min-w-0">
                                    <p class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('app.dashboard_ui.summary') }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ __('app.dashboard_ui.today') }}</p>
                                </div>
                                <span class="shrink-0 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                                    {{ __('app.home_ui.product_preview') }}
                                </span>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    @foreach ([
                                        [__('app.dashboard_ui.today_bookings'), '12'],
                                        [__('app.dashboard_ui.upcoming_bookings'), '7'],
                                        [__('app.dashboard_ui.new_customers'), '4'],
                                        [__('app.dashboard_ui.open_bookings'), '3'],
                                    ] as $stat)
                                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
                                            <p class="text-[10px] font-semibold leading-4 text-slate-400">{{ $stat[0] }}</p>
                                            <p class="mt-2 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $stat[1] }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                                        <p class="text-xs font-extrabold text-slate-900 dark:text-white">{{ __('app.dashboard_ui.upcoming') }}</p>
                                        <span class="text-[10px] font-semibold text-slate-400">{{ __('app.dashboard_ui.view_all') }}</span>
                                    </div>
                                    @foreach ([
                                        ['09:00', __('app.home_ui.preview_service_1'), 'A'],
                                        ['11:30', __('app.home_ui.preview_service_2'), 'M'],
                                        ['14:00', __('app.home_ui.preview_service_3'), 'S'],
                                    ] as $appointment)
                                        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 dark:border-slate-800">
                                            <span class="w-11 shrink-0 text-xs font-extrabold text-brand-indigo">{{ $appointment[0] }}</span>
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-extrabold text-slate-500 dark:bg-slate-800 dark:text-slate-300">{{ $appointment[2] }}</span>
                                            <span class="min-w-0 truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $appointment[1] }}</span>
                                            <span class="ms-auto shrink-0 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">{{ __('app.dashboard_ui.status_confirmed') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.features_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-brand-navy dark:text-white sm:text-4xl">
                        {{ __('app.home_ui.features_title') }}
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.home_ui.features_description') }}
                    </p>
                </div>

                <div class="mt-10 grid border-y border-slate-200 sm:grid-cols-2 lg:grid-cols-3 dark:border-slate-800">
                    @foreach ([
                        [__('app.home_ui.feature_bookings_title'), __('app.home_ui.feature_bookings_text')],
                        [__('app.home_ui.feature_calendar_title'), __('app.home_ui.feature_calendar_text')],
                        [__('app.home_ui.feature_services_title'), __('app.home_ui.feature_services_text')],
                        [__('app.home_ui.feature_staff_title'), __('app.home_ui.feature_staff_text')],
                        [__('app.home_ui.feature_customers_title'), __('app.home_ui.feature_customers_text')],
                        [__('app.home_ui.feature_billing_title'), __('app.home_ui.feature_billing_text')],
                    ] as $feature)
                        <article class="border-b border-slate-200 px-0 py-6 sm:px-5 sm:[&:nth-child(odd)]:border-e lg:border-e-0 lg:[&:nth-child(3n+1)]:border-e lg:[&:nth-child(3n+2)]:border-e dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $feature[0] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $feature[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="for-businesses" class="bg-slate-50 dark:bg-slate-900/30">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.businesses_eyebrow') }}</p>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-brand-navy dark:text-white sm:text-3xl">
                            {{ __('app.home_ui.businesses_title') }}
                        </h2>
                    </div>
                    <p class="max-w-xl text-sm leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.home_ui.businesses_description') }}
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        [__('app.home_ui.business_type_clinics'), __('app.home_ui.business_clinics_text')],
                        [__('app.home_ui.business_type_dental'), __('app.home_ui.business_dental_text')],
                        [__('app.home_ui.business_type_salons'), __('app.home_ui.business_salons_text')],
                        [__('app.home_ui.business_type_barbers'), __('app.home_ui.business_barbers_text')],
                    ] as $business)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $business[0] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $business[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.booking_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-brand-navy dark:text-white sm:text-4xl">
                            {{ __('app.home_ui.booking_title') }}
                        </h2>
                        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                            {{ __('app.home_ui.booking_description') }}
                        </p>

                        <div class="mt-8 space-y-3">
                            @foreach ([
                                ['01', __('app.home_ui.booking_step_service')],
                                ['02', __('app.home_ui.booking_step_date')],
                                ['03', __('app.home_ui.booking_step_time')],
                                ['04', __('app.home_ui.booking_step_details')],
                                ['05', __('app.home_ui.booking_step_confirmation')],
                            ] as $step)
                                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-extrabold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $step[0] }}</span>
                                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $step[1] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-800">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.home_ui.preview_label') }}</p>
                                <h3 class="mt-2 text-lg font-extrabold text-slate-900 dark:text-white">{{ __('app.home_ui.booking_preview_title') }}</h3>
                            </div>
                            <div class="space-y-5 p-4 sm:p-5">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.home_ui.booking_service_label') }}</p>
                                    <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-brand-indigo bg-brand-indigo/5 px-3 py-3 dark:bg-indigo-400/10">
                                        <span class="min-w-0 truncate text-sm font-extrabold text-slate-900 dark:text-white">{{ __('app.home_ui.preview_service_1') }}</span>
                                        <span class="shrink-0 text-xs font-bold text-brand-indigo">{{ __('app.home_ui.preview_price') }}</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.home_ui.booking_date_label') }}</p>
                                    <div class="mt-2 grid grid-cols-4 gap-2">
                                        @foreach (['24','25','26','27'] as $day)
                                            <span class="{{ $day === '26' ? 'border-brand-indigo bg-brand-indigo text-white' : 'border-slate-200 text-slate-600 dark:border-slate-800 dark:text-slate-300' }} rounded-lg border px-2 py-2 text-center text-xs font-extrabold">{{ $day }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.home_ui.booking_time_label') }}</p>
                                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                        @foreach (['09:00','10:30','11:30','13:00','14:00','15:30'] as $time)
                                            <span class="{{ $time === '11:30' ? 'border-brand-indigo bg-brand-indigo text-white' : 'border-slate-200 text-slate-700 dark:border-slate-800 dark:text-slate-200' }} rounded-lg border px-2 py-2.5 text-center text-xs font-bold">{{ $time }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <span class="text-xs font-semibold text-slate-400">{{ __('app.home_ui.booking_secure_note') }}</span>
                                    <span class="shrink-0 rounded-lg bg-brand-navy px-4 py-2 text-xs font-extrabold text-white dark:bg-indigo-500">{{ __('app.home_ui.booking_preview_cta') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq" class="border-b border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.faq_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-brand-navy dark:text-white sm:text-4xl">
                        {{ __('app.home_ui.faq_title') }}
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('app.home_ui.faq_description') }}</p>
                </div>

                <div class="mt-8 divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    @foreach ([
                        [__('app.home_ui.faq_q1'), __('app.home_ui.faq_a1')],
                        [__('app.home_ui.faq_q2'), __('app.home_ui.faq_a2')],
                        [__('app.home_ui.faq_q3'), __('app.home_ui.faq_a3')],
                        [__('app.home_ui.faq_q4'), __('app.home_ui.faq_a4')],
                        [__('app.home_ui.faq_q5'), __('app.home_ui.faq_a5')],
                    ] as $faq)
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-start text-sm font-extrabold text-slate-900 marker:hidden dark:text-white">
                                <span>{{ $faq[0] }}</span>
                                <span class="shrink-0 text-xl font-normal leading-none text-slate-400 transition-transform group-open:rotate-45">+</span>
                            </summary>
                            <p class="max-w-3xl pb-5 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ $faq[1] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
                <div class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr] lg:items-center">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.trust_eyebrow') }}</p>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-brand-navy dark:text-white sm:text-3xl">
                            {{ __('app.home_ui.trust_title') }}
                        </h2>
                        <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ __('app.home_ui.trust_description') }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            __('app.home_ui.trust_auth'),
                            __('app.home_ui.trust_roles'),
                            __('app.home_ui.trust_payments'),
                            __('app.home_ui.trust_booking_controls'),
                        ] as $trust)
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 text-sm font-extrabold text-brand-indigo">✓</span>
                                    <p class="text-sm font-bold leading-6 text-slate-800 dark:text-slate-200">{{ $trust }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-brand-navy text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-12 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('app.home_ui.cta_title') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">{{ __('app.home_ui.cta_description') }}</p>
                </div>
                <a href="{{ route('register') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-bold text-brand-navy transition-colors hover:bg-slate-100">
                    {{ __('app.home_ui.start_free') }}
                </a>
            </div>
        </section>
    </main>
@endsection