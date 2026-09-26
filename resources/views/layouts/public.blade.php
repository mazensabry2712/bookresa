<!DOCTYPE html>
<html class="scroll-smooth" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
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
        <div class="mx-auto flex min-h-[5.5rem] max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="relative flex h-12 w-[250px] shrink-0 items-center overflow-hidden rounded-lg sm:h-14 sm:w-[350px]" aria-label="BookResa">
                <img src="{{ asset('logo.png') }}" alt="BookResa" width="707" height="353" decoding="async" fetchpriority="high" class="absolute inset-x-0 top-1/2 h-auto w-full max-w-none -translate-y-1/2 dark:hidden">
                <img src="{{ asset('logodark.png') }}" alt="BookResa" width="707" height="353" decoding="async" class="absolute inset-x-0 top-1/2 hidden h-auto w-full max-w-none -translate-y-1/2 dark:block">
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
                <div class="relative">
                    <button type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                            data-bookresa-utility-menu
                            aria-expanded="false"
                            aria-controls="bookresa-utility-panel"
                            aria-haspopup="true"
                            aria-label="{{ __('app.language') }} & {{ __('app.theme') }}">
                        <span class="text-lg font-bold leading-none" aria-hidden="true">•••</span>
                    </button>

                    <div id="bookresa-utility-panel"
                         class="invisible absolute end-0 top-[calc(100%+0.6rem)] z-50 w-64 translate-y-1 rounded-xl border border-slate-200 bg-white p-4 opacity-0 shadow-xl shadow-slate-900/10 transition duration-150 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25"
                         data-bookresa-utility-panel
                         aria-hidden="true">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.language') }}</p>
                            <div class="mt-2">
                                <x-locale-switcher />
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.theme') }}</p>
                            <div class="mt-2">
                                <x-theme-toggle />
                            </div>
                        </div>
                    </div>
                </div>

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
                <img src="{{ asset('logo.png') }}" alt="BookResa" width="160" height="36" loading="lazy" decoding="async" class="h-9 w-auto max-w-[160px] object-contain dark:hidden">
                <img src="{{ asset('logodark.png') }}" alt="BookResa" width="160" height="36" loading="lazy" decoding="async" class="hidden h-9 w-auto max-w-[160px] object-contain dark:block">
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
            const navButton = document.querySelector('[data-bookresa-public-menu]');
            const navPanel = document.querySelector('[data-bookresa-public-menu-panel]');
            const firstNavLink = navPanel?.querySelector('a');

            if (navButton && navPanel) {
                const setNavOpen = (open, restoreFocus = true) => {
                    navPanel.classList.toggle('hidden', !open);
                    navPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
                    navButton.setAttribute('aria-expanded', open ? 'true' : 'false');
                    navButton.setAttribute(
                        'aria-label',
                        open
                            ? @json(__('app.home_ui.close_navigation'))
                            : @json(__('app.home_ui.open_navigation'))
                    );

                    if (open) {
                        firstNavLink?.focus();
                    } else if (restoreFocus) {
                        navButton.focus();
                    }
                };

                navButton.addEventListener('click', () => {
                    setNavOpen(navPanel.classList.contains('hidden'));
                });

                navPanel.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', () => setNavOpen(false, false));
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !navPanel.classList.contains('hidden')) {
                        setNavOpen(false);
                    }
                });
            }

            const utilityButton = document.querySelector('[data-bookresa-utility-menu]');
            const utilityPanel = document.querySelector('[data-bookresa-utility-panel]');

            if (utilityButton && utilityPanel) {
                const setUtilityOpen = (open, restoreFocus = true) => {
                    utilityPanel.classList.toggle('invisible', !open);
                    utilityPanel.classList.toggle('opacity-0', !open);
                    utilityPanel.classList.toggle('translate-y-1', !open);
                    utilityPanel.classList.toggle('visible', open);
                    utilityPanel.classList.toggle('translate-y-0', open);
                    utilityPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
                    utilityButton.setAttribute('aria-expanded', open ? 'true' : 'false');

                    if (!open && restoreFocus) {
                        utilityButton.focus();
                    }
                };

                utilityButton.addEventListener('click', () => {
                    setUtilityOpen(utilityPanel.classList.contains('invisible'));
                });

                utilityPanel.querySelectorAll('a, button').forEach((control) => {
                    control.addEventListener('click', () => {
                        if (control.tagName === 'A') {
                            setUtilityOpen(false, false);
                        }
                    });
                });

                document.addEventListener('click', (event) => {
                    if (!utilityPanel.contains(event.target) && !utilityButton.contains(event.target)) {
                        setUtilityOpen(false, false);
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !utilityPanel.classList.contains('invisible')) {
                        setUtilityOpen(false);
                    }
                });
            }
        });
    </script>
</body>
</html>
