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
                            <a href="#features"
                               class="inline-flex min-h-11 w-full min-[420px]:w-auto items-center justify-center px-1 py-3 text-sm font-bold text-slate-700 transition-colors hover:text-brand-navy dark:text-slate-200 dark:hover:text-white">
                                {{ __('app.home_ui.features') }}
                            </a>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-xs font-semibold text-slate-400">
                            <span>{{ __('app.home_ui.proof_bookings') }}</span>
                            <span>{{ __('app.home_ui.proof_team') }}</span>
                            <span>{{ __('app.home_ui.proof_growth') }}</span>
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

                <div class="mt-8 flex flex-wrap gap-x-8 gap-y-3 border-t border-slate-200 pt-6 dark:border-slate-800">
                    @foreach ([
                        __('app.home_ui.business_type_clinics'),
                        __('app.home_ui.business_type_dental'),
                        __('app.home_ui.business_type_salons'),
                        __('app.home_ui.business_type_barbers'),
                    ] as $type)
                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $type }}</span>
                    @endforeach
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