@extends('layouts.public')

@section('content')
    <main>
        <section class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1fr_0.9fr] lg:items-center lg:px-8 lg:py-24">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.eyebrow') }}</p>

                    <h1 class="mt-5 max-w-2xl text-4xl font-extrabold leading-tight tracking-tight text-brand-navy dark:text-white sm:text-5xl lg:text-[3.65rem]">
                        {{ __('app.home_ui.hero_title') }}
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-8 text-slate-600 dark:text-slate-300 sm:text-lg">
                        {{ __('app.home_ui.hero_description') }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            {{ __('app.home_ui.start_free') }}
                        </a>
                        <a href="#how-it-works"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">
                            {{ __('app.home_ui.explore_features') }}
                        </a>
                    </div>

                    <div class="mt-10 flex flex-wrap gap-x-8 gap-y-4 border-t border-slate-200 pt-6 dark:border-slate-800">
                        <div class="max-w-[12rem]">
                            <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.proof_bookings') }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.home_ui.proof_bookings_text') }}</p>
                        </div>
                        <div class="max-w-[12rem]">
                            <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.proof_team') }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.home_ui.proof_team_text') }}</p>
                        </div>
                        <div class="max-w-[12rem]">
                            <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.proof_growth') }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.home_ui.proof_growth_text') }}</p>
                        </div>
                    </div>
                </div>

                <div class="lg:pl-6">
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                        <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950 sm:p-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('logo.png') }}" alt="" class="h-8 w-auto max-w-[120px] object-contain">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('app.home_ui.preview_label') }}</p>
                                    <p class="mt-0.5 truncate text-lg font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.preview_title') }}</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('app.home_ui.date') ?? __('app.date') }}</p>
                                <div class="mt-2 grid grid-cols-5 gap-2">
                                    @foreach (['Mon 22', 'Tue 23', 'Wed 24', 'Thu 25', 'Fri 26'] as $date)
                                        <div class="{{ $loop->index === 2 ? 'border-brand-indigo bg-indigo-50 text-brand-indigo dark:border-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'border-slate-200 text-slate-600 dark:border-slate-800 dark:text-slate-300' }} rounded-xl border px-2 py-3 text-center">
                                            <span class="block text-[10px] font-semibold uppercase">{{ strtok($date, ' ') }}</span>
                                            <span class="mt-1 block text-sm font-bold">{{ str_replace(strtok($date, ' '), '', $date) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-6">
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('app.home_ui.preview_title') }}</p>
                                <div class="mt-3 space-y-2.5">
                                    @foreach ([
                                        ['service' => __('app.home_ui.preview_service_1'), 'time' => '09:00'],
                                        ['service' => __('app.home_ui.preview_service_2'), 'time' => '11:30'],
                                        ['service' => __('app.home_ui.preview_service_3'), 'time' => '14:00'],
                                    ] as $item)
                                        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ $item['service'] }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('app.home_ui.available_times') }}</p>
                                            </div>
                                            <span class="shrink-0 text-sm font-extrabold text-brand-indigo">{{ $item['time'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ route('register') }}"
                               class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                {{ __('app.home_ui.start_free') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="border-b border-slate-200 dark:border-slate-800">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.features_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">
                        {{ __('app.home_ui.features_title') }}
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.home_ui.features_description') }}
                    </p>
                </div>

                <div class="mt-10 grid gap-x-12 gap-y-10 md:grid-cols-2">
                    @foreach ([
                        [__('app.home_ui.feature_bookings_title'), __('app.home_ui.feature_bookings_text')],
                        [__('app.home_ui.feature_calendar_title'), __('app.home_ui.feature_calendar_text')],
                        [__('app.home_ui.feature_services_title'), __('app.home_ui.feature_services_text')],
                        [__('app.home_ui.feature_staff_title'), __('app.home_ui.feature_staff_text')],
                        [__('app.home_ui.feature_customers_title'), __('app.home_ui.feature_customers_text')],
                        [__('app.home_ui.feature_billing_title'), __('app.home_ui.feature_billing_text')],
                    ] as $feature)
                        <article class="border-t border-slate-200 pt-5 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ $feature[0] }}</h3>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $feature[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="how-it-works" class="bg-slate-50/70 dark:bg-slate-900/40">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.workflow_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">
                        {{ __('app.home_ui.workflow_title') }}
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.home_ui.workflow_description') }}
                    </p>
                </div>

                <div class="mt-10 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['01', __('app.home_ui.step_1_title'), __('app.home_ui.step_1_text')],
                        ['02', __('app.home_ui.step_2_title'), __('app.home_ui.step_2_text')],
                        ['03', __('app.home_ui.step_3_title'), __('app.home_ui.step_3_text')],
                        ['04', __('app.home_ui.step_4_title'), __('app.home_ui.step_4_text')],
                    ] as $step)
                        <article>
                            <span class="text-xs font-bold tracking-[0.12em] text-brand-indigo">{{ $step[0] }}</span>
                            <h3 class="mt-3 text-base font-bold text-slate-950 dark:text-white">{{ $step[1] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $step[2] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="for-businesses" class="border-y border-slate-200 dark:border-slate-800">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-start lg:px-8 lg:py-20">
                <div>
                    <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.businesses_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">
                        {{ __('app.home_ui.businesses_title') }}
                    </h2>
                    <p class="mt-4 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.home_ui.businesses_description') }}
                    </p>
                </div>

                <div class="grid divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-800 dark:border-slate-800 sm:grid-cols-2 sm:divide-y-0 sm:divide-x sm:border-y-0 sm:border-s dark:sm:divide-slate-800">
                    @foreach ([
                        __('app.home_ui.business_type_clinics'),
                        __('app.home_ui.business_type_dental'),
                        __('app.home_ui.business_type_salons'),
                        __('app.home_ui.business_type_barbers'),
                    ] as $type)
                        <div class="px-4 py-5 text-base font-semibold text-slate-900 dark:text-white sm:px-6 sm:first:border-b sm:first:py-0 sm:first:pb-5 sm:nth-child(2):border-b sm:nth-child(2):py-0 sm:nth-child(2):pb-5">
                            {{ $type }}
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="flex flex-col gap-6 border-y border-slate-200 py-10 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.home_ui.cta_eyebrow') }}</p>
                        <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-3xl">
                            {{ __('app.home_ui.cta_title') }}
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('app.home_ui.cta_description') }}</p>
                    </div>
                    <a href="{{ route('register') }}"
                       class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-brand-indigo px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-600">
                        {{ __('app.home_ui.start_free') }}
                    </a>
                </div>
            </div>
        </section>
    </main>
@endsection
