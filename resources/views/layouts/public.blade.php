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
    <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto flex min-h-[4.75rem] max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center rounded-lg" aria-label="BookResa">
                <img src="{{ asset('logo.png') }}" alt="BookResa" class="h-11 w-auto max-w-[185px] object-contain">
            </a>

            <nav class="hidden flex-1 items-center justify-center gap-7 lg:flex" aria-label="{{ __('app.home_ui.primary_navigation') }}">
                <a href="#features" class="text-sm font-semibold text-slate-600 transition hover:text-brand-navy dark:text-slate-300 dark:hover:text-white">
                    {{ __('app.home_ui.features') }}
                </a>
                <a href="#for-businesses" class="text-sm font-semibold text-slate-600 transition hover:text-brand-navy dark:text-slate-300 dark:hover:text-white">
                    {{ __('app.home_ui.for_businesses') }}
                </a>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 lg:flex">
                <x-locale-switcher compact />
                <x-theme-toggle compact />
                <a href="{{ route('login') }}" class="px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:text-brand-navy dark:text-slate-200 dark:hover:text-white">
                    {{ __('app.home_ui.login') }}
                </a>
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                    {{ __('app.home_ui.get_started') }}
                </a>
            </div>

            <button type="button"
                    class="ms-auto rounded-lg border border-slate-200 p-2.5 text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 lg:hidden"
                    data-bookresa-public-menu
                    aria-expanded="false"
                    aria-controls="bookresa-public-menu"
                    aria-label="{{ __('app.home_ui.open_navigation') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>

        <div id="bookresa-public-menu"
             class="hidden border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950 lg:hidden"
             data-bookresa-public-menu-panel
             aria-hidden="true">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6">
                <nav class="grid gap-1" aria-label="{{ __('app.home_ui.primary_navigation') }}">
                    <a href="#features" class="rounded-lg px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-900">
                        {{ __('app.home_ui.features') }}
                    </a>
                    <a href="#for-businesses" class="rounded-lg px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-900">
                        {{ __('app.home_ui.for_businesses') }}
                    </a>
                </nav>

                <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <x-locale-switcher />
                    <x-theme-toggle />
                    <div class="ms-auto flex gap-2">
                        <a href="{{ route('login') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-bold dark:border-slate-700">
                            {{ __('app.home_ui.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-bold text-white dark:bg-indigo-500">
                            {{ __('app.home_ui.get_started') }}
                        </a>
                    </div>
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
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-7 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="flex items-center gap-4">
                <img src="{{ asset('logo.png') }}" alt="BookResa" class="h-8 w-auto max-w-[145px] object-contain">
                <span class="text-xs text-slate-400">© {{ now()->year }} BookResa</span>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-500">
                <a href="{{ route('login') }}" class="hover:text-brand-navy dark:hover:text-white">{{ __('app.home_ui.login') }}</a>
                <a href="{{ route('register') }}" class="hover:text-brand-navy dark:hover:text-white">{{ __('app.home_ui.get_started') }}</a>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const button = document.querySelector('[data-bookresa-public-menu]');
            const panel = document.querySelector('[data-bookresa-public-menu-panel]');
            const firstLink = panel?.querySelector('a');
            const desktopQuery = window.matchMedia('(min-width: 1024px)');

            if (!button || !panel) {
                return;
            }

            const setOpen = (open, restoreFocus = true) => {
                panel.classList.toggle('hidden', !open);
                panel.setAttribute('aria-hidden', open ? 'false' : 'true');
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
                button.setAttribute(
                    'aria-label',
                    open
                        ? @json(__('app.home_ui.close_navigation'))
                        : @json(__('app.home_ui.open_navigation'))
                );

                if (open) {
                    firstLink?.focus();
                } else if (restoreFocus) {
                    button.focus();
                }
            };

            button.addEventListener('click', () => setOpen(panel.classList.contains('hidden')));

            panel.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => setOpen(false, false));
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !panel.classList.contains('hidden')) {
                    setOpen(false);
                }
            });

            desktopQuery.addEventListener('change', (event) => {
                if (event.matches) {
                    setOpen(false, false);
                }
            });
        });
    </script>
</body>
</html>
