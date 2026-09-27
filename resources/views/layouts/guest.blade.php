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

                    <div class="relative">
                        <button type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                                data-bookresa-guest-utility
                                aria-expanded="false"
                                aria-controls="bookresa-guest-utility-panel"
                                aria-haspopup="true"
                                aria-label="{{ __('app.language') }} & {{ __('app.theme') }}">
                            <span class="text-lg font-bold leading-none" aria-hidden="true">•••</span>
                        </button>

                        <div id="bookresa-guest-utility-panel"
                             class="invisible absolute end-0 top-[calc(100%+0.6rem)] z-50 w-60 translate-y-1 rounded-xl border border-slate-200 bg-white p-3 opacity-0 shadow-xl shadow-slate-900/10 transition duration-150 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25"
                             data-bookresa-guest-utility-panel
                             aria-hidden="true">
                            <div>
                                <p class="px-1 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.language') }}</p>
                                <div class="mt-1">
                                    <x-locale-switcher compact />
                                </div>
                            </div>

                            <div class="mt-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                                <p class="px-1 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.theme') }}</p>
                                <div class="mt-1">
                                    <x-theme-toggle compact />
                                </div>
                            </div>
                        </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const button = document.querySelector('[data-bookresa-guest-utility]');
            const panel = document.querySelector('[data-bookresa-guest-utility-panel]');

            if (!button || !panel) {
                return;
            }

            const setOpen = (open, restoreFocus = true) => {
                panel.classList.toggle('invisible', !open);
                panel.classList.toggle('opacity-0', !open);
                panel.classList.toggle('translate-y-1', !open);
                panel.classList.toggle('visible', open);
                panel.classList.toggle('translate-y-0', open);
                panel.setAttribute('aria-hidden', open ? 'false' : 'true');
                button.setAttribute('aria-expanded', open ? 'true' : 'false');

                if (!open && restoreFocus) {
                    button.focus();
                }
            };

            button.addEventListener('click', () => {
                setOpen(panel.classList.contains('invisible'));
            });

            panel.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => setOpen(false, false));
            });

            document.addEventListener('click', (event) => {
                if (!panel.contains(event.target) && !button.contains(event.target)) {
                    setOpen(false, false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !panel.classList.contains('invisible')) {
                    setOpen(false);
                }
            });
        });
    </script>

</body>
</html>
