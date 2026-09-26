@extends('layouts.public')

@section('content')
    <main>
        <section class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div class="grid items-center gap-14 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20">
                    <div class="max-w-xl">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.eyebrow') }}</p>

                        <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-[-0.04em] text-brand-navy dark:text-white sm:text-5xl lg:text-6xl">
                            {{ __('app.home_ui.hero_title') }}
                        </h1>

                        <p class="mt-6 max-w-lg text-base leading-8 text-slate-600 dark:text-slate-300 sm:text-lg">
                            {{ __('app.home_ui.hero_description') }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a href="{{ route('register') }}"
                               class="inline-flex min-h-11 items-center justify-center rounded-lg bg-brand-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                {{ __('app.home_ui.start_free') }}
                            </a>
                            <a href="#features"
                               class="inline-flex min-h-11 items-center justify-center px-1 py-3 text-sm font-bold text-slate-700 transition hover:text-brand-navy dark:text-slate-200 dark:hover:text-white">
                                {{ __('app.home_ui.features') }}
                            </a>
                        </div>

                        <div class="mt-9 flex flex-wrap gap-x-6 gap-y-2 text-xs font-semibold text-slate-400">
                            <span>{{ __('app.home_ui.proof_bookings') }}</span>
                            <span>{{ __('app.home_ui.proof_team') }}</span>
                            <span>{{ __('app.home_ui.proof_growth') }}</span>
                        </div>
                    </div>

                    <div class="lg:ps-4">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_16px_45px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                                <div class="flex min-w-0 items-center gap-3">
                                    <img src="{{ asset('logo.png') }}" alt="BookResa" class="h-7 w-auto max-w-[115px] object-contain">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.preview_business') }}</p>
                                        <p class="truncate text-xs text-slate-400">{{ __('app.home_ui.preview_label') }}</p>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-slate-400">{{ __('app.home_ui.preview_timezone') }}</span>
                            </div>

                            <div class="grid md:grid-cols-[0.72fr_1.28fr]">
                                <div class="border-b border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950 md:border-b-0 md:border-e">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.home_ui.preview_service_label') }}</p>

                                    <div class="mt-4 space-y-2">
                                        @foreach ([
                                            [__('app.home_ui.preview_service_1'), __('app.home_ui.preview_service_meta'), __('app.home_ui.preview_price'), true],
                                            [__('app.home_ui.preview_service_2'), __('app.home_ui.preview_customer_2'), null, false],
                                            [__('app.home_ui.preview_service_3'), __('app.home_ui.preview_customer_3'), null, false],
                                        ] as $service)
                                            <div class="{{ $service[3] ? 'border-indigo-200 dark:border-indigo-900' : 'border-slate-200 dark:border-slate-800' }} rounded-xl border bg-white p-3 dark:bg-slate-900">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $service[0] }}</p>
                                                        <p class="mt-1 text-xs text-slate-400">{{ $service[1] }}</p>
                                                    </div>
                                                    @if ($service[2])
                                                        <span class="text-xs font-extrabold text-brand-indigo">{{ $service[2] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="p-5 sm:p-6">
                                    <div class="flex items-end justify-between gap-4">
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.home_ui.preview_time_label') }}</p>
                                            <h2 class="mt-2 text-xl font-extrabold text-slate-950 dark:text-white">{{ __('app.home_ui.preview_title') }}</h2>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-400">{{ __('app.home_ui.preview_timezone') }}</span>
                                    </div>

                                    <div class="mt-5 border-y border-slate-100 py-4 dark:border-slate-800">
                                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-bold uppercase text-slate-400">
                                            <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                                        </div>
                                        <div class="mt-2 grid grid-cols-7 gap-1">
                                            @foreach (['21','22','23','24','25','26','27','28','29','30','01','02','03','04','05','06','07','08','09','10','11'] as $day)
                                                <span class="{{ $day === '01' ? 'bg-brand-indigo text-white' : 'text-slate-600 dark:text-slate-300' }} rounded-md px-1 py-1.5 text-center text-[11px] font-bold">
                                                    {{ $day }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                        @foreach (['09:00', '10:30', '11:30', '13:00', '14:00', '15:30'] as $time)
                                            <span class="{{ $time === '11:30' ? 'border-brand-indigo bg-brand-indigo text-white' : 'border-slate-200 text-slate-700 dark:border-slate-800 dark:text-slate-200' }} rounded-lg border px-3 py-2.5 text-center text-sm font-bold">
                                                {{ $time }}
                                            </span>
                                        @endforeach
                                    </div>

                                    <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                                        <div>
                                            <p class="text-xs text-slate-400">{{ __('app.home_ui.preview_service_label') }}</p>
                                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ __('app.home_ui.preview_service_1') }}</p>
                                        </div>
                                        <span class="text-sm font-extrabold text-brand-indigo">{{ __('app.home_ui.preview_price') }}</span>
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
                <div class="grid gap-10 lg:grid-cols-[0.7fr_1.3fr] lg:gap-20">
                    <div class="max-w-sm">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.features_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-brand-navy dark:text-white sm:text-4xl">
                            {{ __('app.home_ui.features_title') }}
                        </h2>
                    </div>

                    <div>
                        <p class="max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                            {{ __('app.home_ui.features_description') }}
                        </p>

                        <div class="mt-10 border-t border-slate-200 dark:border-slate-800">
                            @foreach ([
                                [__('app.home_ui.feature_bookings_title'), __('app.home_ui.feature_bookings_text')],
                                [__('app.home_ui.feature_calendar_title'), __('app.home_ui.feature_calendar_text')],
                                [__('app.home_ui.feature_services_title'), __('app.home_ui.feature_services_text')],
                                [__('app.home_ui.feature_staff_title'), __('app.home_ui.feature_staff_text')],
                                [__('app.home_ui.feature_customers_title'), __('app.home_ui.feature_customers_text')],
                                [__('app.home_ui.feature_billing_title'), __('app.home_ui.feature_billing_text')],
                            ] as $feature)
                                <article class="grid gap-2 border-b border-slate-200 py-5 dark:border-slate-800 sm:grid-cols-[11rem_1fr] sm:gap-8">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $feature[0] }}</h3>
                                    <p class="max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $feature[1] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
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

                <div class="mt-8 grid border-y border-slate-200 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-800">
                    @foreach ([
                        __('app.home_ui.business_type_clinics'),
                        __('app.home_ui.business_type_dental'),
                        __('app.home_ui.business_type_salons'),
                        __('app.home_ui.business_type_barbers'),
                    ] as $type)
                        <div class="border-b border-slate-200 px-1 py-5 text-sm font-bold text-slate-900 last:border-b-0 sm:px-5 sm:[&:nth-child(odd)]:border-e lg:border-b-0 lg:border-e lg:last:border-e-0 dark:border-slate-800 dark:text-white">
                            {{ $type }}
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-brand-navy text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('app.home_ui.cta_title') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">{{ __('app.home_ui.cta_description') }}</p>
                </div>
                <a href="{{ route('register') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-bold text-brand-navy transition hover:bg-slate-100">
                    {{ __('app.home_ui.start_free') }}
                </a>
            </div>
        </section>
    </main>
@endsection
