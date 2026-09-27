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
        <div class="grid w-full gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.8fr)] lg:items-center lg:gap-14">
            <section class="hidden min-w-0 lg:block">
                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white/80 p-7 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-black/15 xl:p-8">
                    <div class="relative flex h-10 w-[165px] items-center overflow-hidden rounded-lg">
                        <img src="{{ asset('logo.png') }}"
                             alt="BookResa"
                             width="707"
                             height="353"
                             decoding="async"
                             class="absolute inset-x-0 top-1/2 h-auto w-full max-w-none -translate-y-1/2 dark:hidden">
                        <img src="{{ asset('logodark.png') }}"
                             alt="BookResa"
                             width="707"
                             height="353"
                             decoding="async"
                             class="absolute inset-x-0 top-1/2 hidden h-auto w-full max-w-none -translate-y-1/2 dark:block">
                    </div>

                    <p class="mt-8 text-sm font-semibold text-brand-indigo">{{ __('app.auth_platform_eyebrow') }}</p>
                    <h1 class="mt-3 max-w-xl text-4xl font-extrabold leading-[1.08] tracking-[-0.04em] text-slate-950 dark:text-white xl:text-[3.15rem]">
                        {{ __('app.auth_platform_title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">
                        {{ __('app.auth_platform_message') }}
                    </p>

                    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3.5 dark:border-slate-800">
                            <div class="min-w-0">
                                <p class="text-xs font-extrabold text-slate-900 dark:text-white">{{ __('app.dashboard_ui.summary') }}</p>
                                <p class="mt-1 text-[10px] font-medium text-slate-400">{{ __('app.dashboard_ui.today') }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-400 dark:border-slate-700 dark:bg-slate-900">
                                BookResa
                            </span>
                        </div>

                        <div class="space-y-3 p-4">
                            <div class="grid grid-cols-2 gap-2.5">
                                @foreach ([
                                    [__('app.dashboard_ui.today_bookings'), '12'],
                                    [__('app.dashboard_ui.upcoming_bookings'), '7'],
                                    [__('app.dashboard_ui.new_customers'), '4'],
                                    [__('app.dashboard_ui.open_bookings'), '3'],
                                ] as $stat)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                        <p class="text-[9px] font-semibold leading-4 text-slate-400">{{ $stat[0] }}</p>
                                        <p class="mt-1.5 text-lg font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $stat[1] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                                <div class="border-b border-slate-100 px-3 py-2.5 dark:border-slate-800">
                                    <p class="text-[10px] font-extrabold text-slate-900 dark:text-white">{{ __('app.dashboard_ui.upcoming') }}</p>
                                </div>
                                @foreach ([
                                    ['09:00', __('app.home_ui.preview_service_1'), 'A'],
                                    ['11:30', __('app.home_ui.preview_service_2'), 'M'],
                                    ['14:00', __('app.home_ui.preview_service_3'), 'S'],
                                ] as $appointment)
                                    <div class="flex items-center gap-2.5 border-b border-slate-100 px-3 py-2.5 last:border-b-0 dark:border-slate-800">
                                        <span class="w-10 shrink-0 text-[10px] font-extrabold text-brand-indigo">{{ $appointment[0] }}</span>
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[9px] font-extrabold text-slate-500 dark:bg-slate-800 dark:text-slate-300">{{ $appointment[2] }}</span>
                                        <span class="min-w-0 truncate text-[10px] font-bold text-slate-700 dark:text-slate-200">{{ $appointment[1] }}</span>
                                        <span class="ms-auto shrink-0 text-[9px] font-semibold text-emerald-600 dark:text-emerald-400">{{ __('app.dashboard_ui.status_confirmed') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-md lg:mx-0 lg:ms-auto">
                <div class="mb-5 flex items-center justify-between gap-3 px-1">
                    <a href="{{ route('home') }}" class="relative flex h-9 w-[135px] items-center overflow-hidden rounded-md" aria-label="BookResa">
                        <img src="{{ asset('logo.png') }}"
                             alt="BookResa"
                             width="707"
                             height="353"
                             decoding="async"
                             class="absolute inset-x-0 top-1/2 h-auto w-full max-w-none -translate-y-1/2 dark:hidden">
                        <img src="{{ asset('logodark.png') }}"
                             alt="BookResa"
                             width="707"
                             height="353"
                             decoding="async"
                             class="absolute inset-x-0 top-1/2 hidden h-auto w-full max-w-none -translate-y-1/2 dark:block">
                    </a>

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

            panel.querySelectorAll('a, button').forEach((control) => {
                control.addEventListener('click', () => {
                    if (control.tagName === 'A') {
                        setOpen(false, false);
                    }
                });
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