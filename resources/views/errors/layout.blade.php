@php
    $supportedLocales = array_values(config('bookresa.locales', ['en']));
    $requestedLocale = request()->query('locale');

    if (is_string($requestedLocale) && in_array($requestedLocale, $supportedLocales, true)) {
        app()->setLocale($requestedLocale);
    }

    if (isset($code)) {
        $title = __('app.errors_ui.title_'.$code);
        $message = __('app.errors_ui.message_'.$code);
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">
    <title>{{ $title }} — BookResa</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-white">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <section class="w-full max-w-lg rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20 sm:p-10">
            <a href="{{ route('home') }}" class="mx-auto flex w-fit items-center gap-2.5 rounded-xl" aria-label="BookResa">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-navy text-sm font-extrabold text-white">B</span>
                <span class="text-lg font-extrabold tracking-tight">BookResa</span>
            </a>

            <div class="mt-8">
                <p class="text-6xl font-black tracking-[-0.04em] text-brand-indigo sm:text-7xl">{{ $code }}</p>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $title }}</h1>
                <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-slate-500 dark:text-slate-400">{{ $message }}</p>
            </div>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('home') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                    {{ __('app.errors_ui.home') }}
                </a>
                <a href="{{ route('login') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('app.errors_ui.login') }}
                </a>
            </div>
        </section>
    </main>
</body>
</html>
