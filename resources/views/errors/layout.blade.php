@php
    $supportedLocales = array_values(config('bookresa.locales', ['en']));
    $requestedLocale = request()->query('locale');

    if (is_string($requestedLocale) && in_array($requestedLocale, $supportedLocales, true)) {
        app()->setLocale($requestedLocale);
    }

    $title = __('app.errors_ui.title_'.$code);
    $message = __('app.errors_ui.message_'.$code);
    $context = __('app.errors_ui.context_'.$code);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">
    <title>{{ $code }} — {{ $title }} — BookResa</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="br-shell relative min-h-screen overflow-x-hidden antialiased">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-28 -top-28 h-80 w-80 rounded-full bg-brand-indigo/10 blur-3xl dark:bg-indigo-400/10"></div>
        <div class="absolute -bottom-36 -right-28 h-96 w-96 rounded-full bg-brand-navy/10 blur-3xl dark:bg-indigo-300/10"></div>
    </div>

    <main class="relative mx-auto flex min-h-screen w-full max-w-2xl flex-col px-4 py-5 sm:px-6 sm:py-7">
        <header class="flex items-center justify-between">
            <a href="{{ route('home') }}"
               class="relative flex h-11 w-[170px] items-center overflow-hidden rounded-lg sm:h-12 sm:w-[190px]"
               aria-label="BookResa">
                <img src="{{ asset('logo.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     fetchpriority="high"
                     class="absolute inset-x-1/2 top-1/2 h-auto w-[230px] max-w-none -translate-x-1/2 -translate-y-1/2 scale-110 dark:hidden">
                <img src="{{ asset('logodark.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     class="absolute inset-x-1/2 top-1/2 hidden h-auto w-[230px] max-w-none -translate-x-1/2 -translate-y-1/2 scale-110 dark:block">
            </a>

            <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-[11px] font-bold text-slate-500 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-400">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-indigo"></span>
                {{ __('app.errors_ui.eyebrow') }}
            </div>
        </header>

        <section class="my-auto py-12 sm:py-16">
            <div class="br-panel overflow-hidden rounded-[2rem]">
                <div class="border-b border-slate-200/80 px-6 py-5 dark:border-slate-800 sm:px-9">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-extrabold uppercase tracking-[0.16em] text-brand-indigo dark:text-indigo-300">
                            {{ $context }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-extrabold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            {{ $code }}
                        </span>
                    </div>
                </div>

                <div class="px-6 py-9 sm:px-9 sm:py-11">
                    <div class="grid gap-8 sm:grid-cols-[auto_1fr] sm:items-start sm:gap-10">
                        <div class="mx-auto flex h-20 w-20 shrink-0 items-center justify-center rounded-[1.5rem] bg-brand-indigo/10 text-brand-indigo ring-1 ring-inset ring-brand-indigo/10 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/10 sm:mx-0 sm:h-24 sm:w-24">
                            <svg viewBox="0 0 24 24" class="h-10 w-10 sm:h-11 sm:w-11" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                @if ($code === 403)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 10V8a4 4 0 1 0-8 0v2m-1 0h10a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Zm4 4h2"/>
                                @elseif ($code === 404)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 9 6 6m0-6-6 6M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    <path stroke-linecap="round" d="M8.5 16.5h.01m7 0h.01"/>
                                @elseif ($code === 419)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                @elseif ($code === 429)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8m-4 0v3m5 5a5 5 0 1 1-10 0c0-3 3-5 5-5s5 2 5 5Zm-8 7h6"/>
                                @elseif ($code === 500)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 14.5 2.5-2.5 2.5 2.5m-5-5h.01m5 0h.01M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.3 3.7 2.9 16.5A2 2 0 0 0 4.6 19.5h14.8a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/>
                                @endif
                            </svg>
                        </div>

                        <div class="min-w-0 text-center sm:text-start">
                            <p class="text-[clamp(4.5rem,16vw,7.5rem)] font-black leading-none tracking-[-0.08em] text-slate-900/10 dark:text-white/10">
                                {{ $code }}
                            </p>

                            <h1 class="mt-2 text-3xl font-extrabold tracking-[-0.04em] text-brand-navy dark:text-white sm:text-4xl">
                                {{ $title }}
                            </h1>

                            <p class="mt-4 max-w-xl text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-base sm:leading-8">
                                {{ $message }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-9 flex flex-col gap-3 border-t border-slate-200/80 pt-7 sm:flex-row sm:items-center dark:border-slate-800">
                        @if ($code === 419 || $code === 429 || $code === 500 || $code === 503)
                            <button type="button"
                                    onclick="window.location.reload()"
                                    class="inline-flex min-h-12 flex-1 items-center justify-center rounded-xl bg-brand-navy px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-indigo focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-offset-slate-950">
                                {{ __('app.errors_ui.try_again') }}
                            </button>
                        @else
                            <a href="{{ route('home') }}"
                               class="inline-flex min-h-12 flex-1 items-center justify-center rounded-xl bg-brand-navy px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-indigo focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-offset-slate-950">
                                {{ __('app.errors_ui.home') }}
                            </a>
                        @endif

                        <a href="{{ route('home') }}"
                           class="inline-flex min-h-12 flex-1 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-indigo focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900 dark:focus:ring-offset-slate-950">
                            {{ __('app.errors_ui.home') }}
                        </a>

                        <a href="{{ route('login') }}"
                           class="inline-flex min-h-12 items-center justify-center rounded-xl px-4 py-3 text-sm font-bold text-slate-500 transition-colors hover:text-brand-indigo dark:text-slate-400 dark:hover:text-indigo-300">
                            {{ __('app.errors_ui.login') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <footer class="pb-2 text-center text-xs font-medium text-slate-400 dark:text-slate-600">
            © {{ now()->year }} BookResa
        </footer>
    </main>
</body>
</html>
