<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">
    <title>@yield('title', 'BookResa')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="br-shell min-h-screen antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid w-full gap-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(420px,0.75fr)] lg:items-center">
            <section class="hidden lg:block">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-brand-indigo">BookResa</p>
                <h1 class="mt-4 max-w-xl text-4xl font-extrabold leading-tight tracking-[-0.03em] text-slate-950 dark:text-white">
                    {{ __('app.auth_platform_title') }}
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">
                    {{ __('app.auth_platform_message') }}
                </p>

                <div class="mt-8 grid max-w-xl gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['title' => __('app.auth_benefit_booking'), 'text' => __('app.auth_benefit_booking_text')],
                        ['title' => __('app.auth_benefit_customers'), 'text' => __('app.auth_benefit_customers_text')],
                        ['title' => __('app.auth_benefit_growth'), 'text' => __('app.auth_benefit_growth_text')],
                    ] as $benefit)
                        <div class="br-panel p-4">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $benefit['title'] }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $benefit['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mx-auto w-full max-w-md lg:mx-0 lg:ms-auto">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <a href="{{ route('home') }}" class="text-lg font-extrabold tracking-tight text-slate-950 dark:text-white">BookResa</a>
                    <div class="flex items-center gap-2">
                        <x-locale-switcher />
                        <x-theme-toggle />
                    </div>
                </div>

                <div class="br-panel p-5 sm:p-7">
                    @if (session('status'))
                        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                            <ul class="space-y-1.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </section>
        </div>
    </main>
</body>
</html>
