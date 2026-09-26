@extends('layouts.public')

@section('content')
    <main>
        <section class="relative overflow-hidden">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 pb-20 pt-16 sm:px-6 sm:pb-24 sm:pt-20 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-8 lg:pt-24">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-300">
                        {{ __('app.home_ui.eyebrow') }}
                    </span>

                    <h1 class="mt-6 max-w-2xl text-4xl font-extrabold leading-[1.08] tracking-[-0.035em] text-brand-navy dark:text-white sm:text-5xl lg:text-6xl">
                        {{ __('app.home_ui.hero_title') }}
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-8 text-slate-600 dark:text-slate-300 sm:text-lg">
                        {{ __('app.home_ui.hero_description') }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}"
                           class="inline-flex min-h-12 items-center justify-center rounded-xl bg-brand-indigo px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            {{ __('app.home_ui.start_free') }}
                        </a>
                        <a href="#features"
                           class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900">
                            {{ __('app.home_ui.explore_features') }}
                        </a>
                    </div>

                    <div class="mt-8 grid max-w-xl gap-3 sm:grid-cols-3">
                        @foreach ([
                            ['title' => __('app.home_ui.proof_bookings'), 'text' => __('app.home_ui.proof_bookings_text')],
                            ['title' => __('app.home_ui.proof_team'), 'text' => __('app.home_ui.proof_team_text')],
                            ['title' => __('app.home_ui.proof_growth'), 'text' => __('app.home_ui.proof_growth_text')],
                        ] as $proof)
                            <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 dark:border-slate-800 dark:bg-slate-900/85">
                                <p class="text-sm font-bold text-slate-950 dark:text-white">{{ $proof['title'] }}</p>
                                <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $proof['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -inset-8 -z-10 rounded-[3rem] bg-indigo-100/70 blur-3xl dark:bg-indigo-950/20"></div>

                    <div class="rounded-[2rem] border border-slate-200 bg-slate-950 p-4 shadow-2xl dark:border-slate-700">
                        <div class="rounded-[1.5rem] bg-white p-5 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.home_ui.preview_label') }}</p>
                                    <p class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.home_ui.preview_title') }}</p>
                                </div>
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">B</span>
                            </div>

                            <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs font-semibold text-slate-500">
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70"><span class="block text-lg font-extrabold text-slate-950 dark:text-white">08</span>{{ __('app.home_ui.preview_bookings') }}</div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70"><span class="block text-lg font-extrabold text-slate-950 dark:text-white">04</span>{{ __('app.home_ui.preview_staff') }}</div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70"><span class="block text-lg font-extrabold text-slate-950 dark:text-white">12</span>{{ __('app.home_ui.preview_services') }}</div>
                            </div>

                            <div class="mt-5 space-y-2.5">
                                @foreach ([
                                    ['time' => '09:00', 'service' => __('app.home_ui.preview_service_1'), 'customer' => __('app.home_ui.preview_customer_1')],
                                    ['time' => '11:30', 'service' => __('app.home_ui.preview_service_2'), 'customer' => __('app.home_ui.preview_customer_2')],
                                    ['time' => '14:00', 'service' => __('app.home_ui.preview_service_3'), 'customer' => __('app.home_ui.preview_customer_3')],
                                ] as $item)
                                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-800">
                                        <span class="w-14 shrink-0 text-xs font-extrabold text-brand-indigo">{{ $item['time'] }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ $item['service'] }}</p>
                                            <p class="truncate text-xs text-slate-500">{{ $item['customer'] }}</p>
                                        </div>
                                        <span class="ms-auto h-2.5 w-2.5 shrink-0 rounded-full bg-brand-coral" aria-hidden="true"></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="border-y border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/40">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-indigo">{{ __('app.home_ui.features_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">{{ __('app.home_ui.features_title') }}</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('app.home_ui.features_description') }}</p>
                </div>

                <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ([
                        ['title' => __('app.home_ui.feature_bookings_title'), 'text' => __('app.home_ui.feature_bookings_text'), 'mark' => '01'],
                        ['title' => __('app.home_ui.feature_calendar_title'), 'text' => __('app.home_ui.feature_calendar_text'), 'mark' => '02'],
                        ['title' => __('app.home_ui.feature_services_title'), 'text' => __('app.home_ui.feature_services_text'), 'mark' => '03'],
                        ['title' => __('app.home_ui.feature_staff_title'), 'text' => __('app.home_ui.feature_staff_text'), 'mark' => '04'],
                        ['title' => __('app.home_ui.feature_customers_title'), 'text' => __('app.home_ui.feature_customers_text'), 'mark' => '05'],
                        ['title' => __('app.home_ui.feature_billing_title'), 'text' => __('app.home_ui.feature_billing_text'), 'mark' => '06'],
                    ] as $feature)
                        <article class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-xs font-extrabold tracking-[0.14em] text-brand-indigo">{{ $feature['mark'] }}</span>
                                <span class="h-2.5 w-2.5 rounded-full bg-brand-coral" aria-hidden="true"></span>
                            </div>
                            <h3 class="mt-7 text-lg font-bold text-slate-950 dark:text-white">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $feature['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="how-it-works" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-indigo">{{ __('app.home_ui.workflow_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">{{ __('app.home_ui.workflow_title') }}</h2>
                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('app.home_ui.workflow_description') }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['step' => '01', 'title' => __('app.home_ui.step_1_title'), 'text' => __('app.home_ui.step_1_text')],
                        ['step' => '02', 'title' => __('app.home_ui.step_2_title'), 'text' => __('app.home_ui.step_2_text')],
                        ['step' => '03', 'title' => __('app.home_ui.step_3_title'), 'text' => __('app.home_ui.step_3_text')],
                        ['step' => '04', 'title' => __('app.home_ui.step_4_title'), 'text' => __('app.home_ui.step_4_text')],
                    ] as $step)
                        <article class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                            <div class="text-xs font-extrabold tracking-[0.16em] text-brand-coral">{{ $step['step'] }}</div>
                            <h3 class="mt-3 font-bold text-slate-950 dark:text-white">{{ $step['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $step['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="for-businesses" class="border-y border-slate-200 bg-brand-navy text-white dark:border-slate-800">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-[1fr_1fr] lg:items-center">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.16em] text-indigo-300">{{ __('app.home_ui.businesses_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('app.home_ui.businesses_title') }}</h2>
                        <p class="mt-4 max-w-xl text-base leading-7 text-slate-300">{{ __('app.home_ui.businesses_description') }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            __('app.home_ui.business_type_clinics'),
                            __('app.home_ui.business_type_dental'),
                            __('app.home_ui.business_type_salons'),
                            __('app.home_ui.business_type_barbers'),
                        ] as $type)
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
                                <p class="font-bold">{{ $type }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-[2rem] border border-indigo-100 bg-indigo-50 px-6 py-10 dark:border-indigo-900/50 dark:bg-indigo-950/30 sm:px-10 sm:py-12">
                <div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-indigo">{{ __('app.home_ui.cta_eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-4xl">{{ __('app.home_ui.cta_title') }}</h2>
                        <p class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('app.home_ui.cta_description') }}</p>
                    </div>

                    <a href="{{ route('register') }}"
                       class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-xl bg-brand-indigo px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                        {{ __('app.home_ui.start_free') }}
                    </a>
                </div>
            </div>
        </section>
    </main>
@endsection
