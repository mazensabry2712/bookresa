@extends('layouts.public')

@section('content')
    <main>
        <section class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto grid max-w-7xl gap-14 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:px-8 lg:py-24">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.eyebrow') }}</p>

                    <h1 class="mt-5 max-w-2xl text-4xl font-extrabold leading-[1.08] tracking-[-0.03em] text-brand-navy dark:text-white sm:text-5xl lg:text-[4rem]">
                        {{ __('app.home_ui.hero_title') }}
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-8 text-slate-600 dark:text-slate-300 sm:text-lg">
                        {{ __('app.home_ui.hero_description') }}
                    </p>

                    <div class="mt-8 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                        <a href="{{ route('register') }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            {{ __('app.home_ui.start_free') }}
                        </a>
                        <a href="#how-it-works"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl px-2 py-3 text-sm font-bold text-slate-700 transition hover:text-brand-indigo dark:text-slate-200">
                            {{ __('app.home_ui.explore_features') }}
                        </a>
                    </div>

                    <p class="mt-8 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('app.home_ui.businesses_description') }}
                    </p>
                </div>

                <div class="lg:ps-6">
                    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-950 dark:shadow-black/25">
                        <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('logo.png') }}" alt="BookResa" class="h-8 w-auto max-w-[125px] object-contain">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.preview_business') }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('app.home_ui.preview_title') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 sm:p-6">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('app.home_ui.preview_service_label') }}</p>

                            <div class="mt-3 rounded-2xl border-2 border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900 dark:bg-indigo-950/30">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.preview_service_1') }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('app.home_ui.preview_service_meta') }}</p>
                                    </div>
                                    <span class="text-sm font-extrabold text-brand-indigo">{{ __('app.home_ui.preview_price') }}</span>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('app.home_ui.preview_time_label') }}</p>
                                    <span class="text-xs font-semibold text-slate-400">{{ __('app.home_ui.preview_timezone') }}</span>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    @foreach (['09:00', '10:30', '11:30', '13:00', '14:00', '15:30'] as $time)
                                        <div class="{{ $time === '11:30' ? 'border-brand-indigo bg-brand-indigo text-white' : 'border-slate-200 text-slate-700 dark:border-slate-800 dark:text-slate-200' }} rounded-xl border px-3 py-2.5 text-center text-sm font-bold">
                                            {{ $time }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ route('register') }}"
                               class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                {{ __('app.home_ui.preview_continue') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                    <div class="max-w-md">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.features_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">
                            {{ __('app.home_ui.features_title') }}
                        </h2>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-800">
                        @foreach ([
                            [__('app.home_ui.feature_bookings_title'), __('app.home_ui.feature_bookings_text')],
                            [__('app.home_ui.feature_calendar_title'), __('app.home_ui.feature_calendar_text')],
                            [__('app.home_ui.feature_services_title'), __('app.home_ui.feature_services_text')],
                            [__('app.home_ui.feature_staff_title'), __('app.home_ui.feature_staff_text')],
                            [__('app.home_ui.feature_customers_title'), __('app.home_ui.feature_customers_text')],
                            [__('app.home_ui.feature_billing_title'), __('app.home_ui.feature_billing_text')],
                        ] as $feature)
                            <article class="grid gap-3 border-b border-slate-200 py-5 sm:grid-cols-[12rem_1fr] sm:gap-8 dark:border-slate-800">
                                <h3 class="text-sm font-bold text-slate-950 dark:text-white">{{ $feature[0] }}</h3>
                                <p class="max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $feature[1] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="border-y border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-900/40">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="grid gap-10 lg:grid-cols-[0.75fr_1.25fr]">
                    <div class="max-w-md">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.workflow_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">
                            {{ __('app.home_ui.workflow_title') }}
                        </h2>
                        <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                            {{ __('app.home_ui.workflow_description') }}
                        </p>
                    </div>

                    <ol class="border-t border-slate-200 dark:border-slate-800">
                        @foreach ([
                            ['01', __('app.home_ui.step_1_title'), __('app.home_ui.step_1_text')],
                            ['02', __('app.home_ui.step_2_title'), __('app.home_ui.step_2_text')],
                            ['03', __('app.home_ui.step_3_title'), __('app.home_ui.step_3_text')],
                            ['04', __('app.home_ui.step_4_title'), __('app.home_ui.step_4_text')],
                        ] as $step)
                            <li class="grid gap-3 border-b border-slate-200 py-5 sm:grid-cols-[3rem_1fr] sm:gap-5 dark:border-slate-800">
                                <span class="text-xs font-bold text-brand-indigo">{{ $step[0] }}</span>
                                <div>
                                    <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ $step[1] }}</h3>
                                    <p class="mt-1.5 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $step[2] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        <section id="for-businesses">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="flex flex-col gap-8 border-b border-slate-200 pb-10 sm:flex-row sm:items-end sm:justify-between dark:border-slate-800">
                    <div class="max-w-xl">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.businesses_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">
                            {{ __('app.home_ui.businesses_title') }}
                        </h2>
                    </div>
                    <p class="max-w-lg text-base leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.home_ui.businesses_description') }}
                    </p>
                </div>

                <div class="grid divide-y divide-slate-200 sm:grid-cols-4 sm:divide-x sm:divide-y-0 dark:divide-slate-800">
                    @foreach ([
                        __('app.home_ui.business_type_clinics'),
                        __('app.home_ui.business_type_dental'),
                        __('app.home_ui.business_type_salons'),
                        __('app.home_ui.business_type_barbers'),
                    ] as $type)
                        <div class="py-5 text-sm font-bold text-slate-900 first:ps-0 sm:px-5 dark:text-white">
                            {{ $type }}
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-brand-navy text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-7 px-4 py-14 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-indigo-200">{{ __('app.home_ui.cta_eyebrow') }}</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('app.home_ui.cta_title') }}</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">{{ __('app.home_ui.cta_description') }}</p>
                </div>
                <a href="{{ route('register') }}"
                   class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-brand-navy transition hover:bg-slate-100">
                    {{ __('app.home_ui.start_free') }}
                </a>
            </div>
        </section>
    </main>
@endsection
