<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">

    @php
        $homeCanonical = route('home').'?locale='.app()->getLocale();
        $homeAlternates = collect(config('bookresa.locales', ['en', 'ar']))
            ->map(fn (string $locale): array => [
                'locale' => $locale,
                'url' => route('home').'?locale='.$locale,
            ])
            ->all();
    @endphp

    <x-seo
        :title="__('app.home_ui.meta_title')"
        :description="__('app.home_ui.meta_description')"
        :canonical="$homeCanonical"
        :alternates="$homeAlternates"
        :json-ld="[
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => config('bookresa.name', 'BookResa'),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => __('app.home_ui.meta_description'),
            'url' => $homeCanonical,
        ]"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-slate-950 antialiased dark:bg-slate-950 dark:text-white">
    <header class="sticky top-0 z-50 border-b border-slate-200/90 bg-white/95 backdrop-blur dark:border-slate-800/90 dark:bg-slate-950/95">
        <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-xl" aria-label="BookResa">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-navy text-sm font-extrabold text-white">B</span>
                <span class="text-base font-extrabold tracking-tight">BookResa</span>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-600 dark:text-slate-300 md:flex" aria-label="{{ __('app.home_ui.primary_navigation') }}">
                <a href="#features" class="transition hover:text-brand-indigo">{{ __('app.home_ui.features') }}</a>
                <a href="#how-it-works" class="transition hover:text-brand-indigo">{{ __('app.home_ui.how_it_works') }}</a>
                <a href="#for-businesses" class="transition hover:text-brand-indigo">{{ __('app.home_ui.for_businesses') }}</a>
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                <x-locale-switcher />
                <x-theme-toggle />
                <a href="{{ route('login') }}" class="rounded-xl px-3.5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900">
                    {{ __('app.home_ui.login') }}
                </a>
                <a href="{{ route('register') }}" class="rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                    {{ __('app.home_ui.get_started') }}
                </a>
            </div>

            <button type="button"
                    class="rounded-xl border border-slate-200 p-2.5 text-slate-700 dark:border-slate-700 dark:text-slate-200 md:hidden"
                    data-bookresa-public-menu
                    aria-expanded="false"
                    aria-controls="bookresa-public-menu"
                    aria-label="{{ __('app.home_ui.open_navigation') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>

        <div id="bookresa-public-menu" class="hidden border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950 md:hidden" data-bookresa-public-menu-panel>
            <div class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6">
                <nav class="grid gap-2 text-sm font-semibold" aria-label="{{ __('app.home_ui.primary_navigation') }}">
                    <a href="#features" class="rounded-xl px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-900">{{ __('app.home_ui.features') }}</a>
                    <a href="#how-it-works" class="rounded-xl px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-900">{{ __('app.home_ui.how_it_works') }}</a>
                    <a href="#for-businesses" class="rounded-xl px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-900">{{ __('app.home_ui.for_businesses') }}</a>
                </nav>
                <div class="flex flex-wrap items-center gap-2">
                    <x-locale-switcher />
                    <x-theme-toggle />
                    <a href="{{ route('login') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold dark:border-slate-700">
                        {{ __('app.home_ui.login') }}
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white">
                        {{ __('app.home_ui.get_started') }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="border-b border-emerald-200 bg-emerald-50 dark:border-emerald-900/70 dark:bg-emerald-950/30">
            <div class="mx-auto max-w-7xl px-4 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-200 sm:px-6 lg:px-8" role="status">
                {{ session('status') }}
            </div>
        </div>
    @endif

    @yield('content')

    <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div>
                <p class="text-sm font-extrabold">BookResa</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.home_ui.footer_text') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-500">
                <a href="{{ route('login') }}" class="hover:text-brand-indigo">{{ __('app.home_ui.login') }}</a>
                <a href="{{ route('register') }}" class="hover:text-brand-indigo">{{ __('app.home_ui.get_started') }}</a>
                <span>© {{ now()->year }} BookResa</span>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const button = document.querySelector('[data-bookresa-public-menu]');
            const panel = document.querySelector('[data-bookresa-public-menu-panel]');

            if (!button || !panel) {
                return;
            }

            const setOpen = (open) => {
                panel.classList.toggle('hidden', !open);
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            button.addEventListener('click', () => setOpen(panel.classList.contains('hidden')));
            panel.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setOpen(false);
                }
            });
        });
    </script>
</body>
</html>
