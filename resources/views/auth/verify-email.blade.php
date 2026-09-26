<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.verify_email_title') }} — {{ config('app.name', 'BookResa') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-12">
        <section class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                {{ config('app.name', 'BookResa') }}
            </p>

            <h1 class="mt-2 text-2xl font-bold tracking-tight">
                {{ __('app.verify_email_title') }}
            </h1>

            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ __('app.verify_email_message') }}
            </p>

            @if (session('status') === 'verification-link-sent')
                <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ __('app.verification_link_sent') }}
                </div>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
                    @csrf
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                    >
                        {{ __('app.resend_verification') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button
                        type="submit"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        {{ __('app.logout') }}
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
