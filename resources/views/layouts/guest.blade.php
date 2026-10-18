<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">
    <title>@yield('title', 'BookResa')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="br-shell relative min-h-screen overflow-x-hidden antialiased">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -start-24 -top-24 h-72 w-72 rounded-full bg-indigo-500/8 blur-3xl dark:bg-indigo-400/10"></div>
        <div class="absolute -end-24 bottom-0 h-72 w-72 rounded-full bg-slate-400/10 blur-3xl dark:bg-slate-700/10"></div>
    </div>

    <main class="relative mx-auto flex min-h-screen w-full max-w-md flex-col px-4 py-6 sm:px-6 sm:py-8">
        <header class="flex items-center justify-between">
            <a href="{{ route('home') }}"
               class="relative flex h-11 w-[190px] items-center overflow-hidden rounded-lg sm:h-12 sm:w-[210px]"
               aria-label="BookResa">
                <img src="{{ asset('logo.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     fetchpriority="high"
                     class="absolute inset-x-1/2 top-1/2 h-auto w-[255px] max-w-none -translate-x-1/2 -translate-y-1/2 scale-110 transform-gpu dark:hidden">
                <img src="{{ asset('logodark.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     class="absolute inset-x-1/2 top-1/2 hidden h-auto w-[255px] max-w-none -translate-x-1/2 -translate-y-1/2 scale-110 transform-gpu dark:block">
            </a>

            <div class="flex items-center gap-1.5">
                <x-locale-switcher compact />
                <x-theme-toggle compact />
            </div>
        </header>

        <section class="my-auto py-10 sm:py-14">
            <div class="br-panel rounded-2xl p-6 sm:p-8">
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

        <footer class="pb-2 text-center text-xs text-slate-400 dark:text-slate-500">
            © {{ now()->year }} BookResa
        </footer>
    </main>

</body>
</html>