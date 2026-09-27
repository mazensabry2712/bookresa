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
               class="relative flex h-10 w-[150px] items-center overflow-hidden rounded-md"
               aria-label="BookResa">
                <img src="{{ asset('logo.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     fetchpriority="high"
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
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/80 text-slate-500 shadow-sm backdrop-blur transition hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-white"
                        data-bookresa-guest-utility
                        aria-expanded="false"
                        aria-controls="bookresa-guest-utility-panel"
                        aria-haspopup="true"
                        aria-label="{{ __('app.language') }} & {{ __('app.theme') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="5" cy="12" r="1.5"/>
                        <circle cx="12" cy="12" r="1.5"/>
                        <circle cx="19" cy="12" r="1.5"/>
                    </svg>
                </button>

                <div id="bookresa-guest-utility-panel"
                     class="invisible absolute end-0 top-[calc(100%+0.65rem)] z-50 w-60 translate-y-1 rounded-2xl border border-slate-200 bg-white p-3 opacity-0 shadow-xl shadow-slate-900/10 transition duration-150 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25"
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