<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">
    <title>@yield('title', 'BookResa Admin')</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="br-shell antialiased">
    <div class="br-drawer-backdrop lg:hidden" data-bookresa-sidebar-backdrop aria-hidden="true"></div>

    <div class="min-h-screen lg:flex">
        <aside id="bookresa-sidebar"
               class="br-drawer lg:static lg:z-auto lg:flex lg:w-64 lg:flex-col lg:shrink-0 lg:transform-none"
               data-bookresa-sidebar
               aria-label="{{ __('Admin') }}">
            <div class="flex min-h-16 items-center justify-between gap-3 border-b border-slate-200 px-4 dark:border-slate-800 lg:px-5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg" aria-label="BookResa Admin">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-navy text-sm font-extrabold text-white">B</span>
                    <span>
                        <span class="block text-sm font-extrabold tracking-tight text-slate-950 dark:text-white">BookResa</span>
                        <span class="block text-[11px] font-semibold text-brand-indigo">Admin</span>
                    </span>
                </a>
                <button type="button"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden"
                        data-bookresa-sidebar-close
                        aria-label="{{ __('Close') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ __('Platform') }}</p>
                <nav class="space-y-1" aria-label="{{ __('Admin') }}">
                    @php
                        $adminLinks = [
                            ['route' => 'admin.dashboard', 'label' => __('app.dashboard')],
                            ['route' => 'admin.businesses.index', 'label' => __('Businesses')],
                            ['route' => 'admin.users.index', 'label' => __('app.users')],
                            ['route' => 'admin.subscriptions.index', 'label' => __('app.subscriptions')],
                            ['route' => 'admin.payments.index', 'label' => __('app.payments')],
                            ['route' => 'admin.usage.index', 'label' => __('app.usage')],
                            ['route' => 'admin.reports.index', 'label' => __('app.reports')],
                            ['route' => 'admin.support.index', 'label' => __('app.support')],
                            ['route' => 'admin.settings.index', 'label' => __('app.settings')],
                            ['route' => 'admin.plans.index', 'label' => __('app.plans')],
                        ];
                    @endphp

                    @foreach ($adminLinks as $link)
                        <a href="{{ route($link['route']) }}" data-active="{{ request()->routeIs($link['route']) ? 'true' : 'false' }}" class="br-nav-link">
                            <span class="h-2 w-2 shrink-0 rounded-full bg-slate-300 dark:bg-slate-700" aria-hidden="true"></span>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach

                    <a href="{{ route('home') }}" data-active="false" class="br-nav-link">
                        <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 10.5 12 4l8 6.5M6 9.5V20h12V9.5M10 20v-5h4v5"/></svg></span>
                        <span>{{ __('app.public_site') }}</span>
                    </a>
                </nav>
            </div>

            <div class="border-t border-slate-200 p-3 dark:border-slate-800">
                <div class="rounded-xl br-surface-soft p-3">
                    <p class="truncate text-xs font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-start text-xs font-semibold text-slate-600 transition hover:bg-white dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            {{ __('Log out') }}
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 backdrop-blur dark:border-slate-800/90 dark:bg-slate-900/95">
                <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button"
                                class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 lg:hidden"
                                data-bookresa-sidebar-toggle
                                aria-expanded="false"
                                aria-controls="bookresa-sidebar"
                                aria-label="{{ __('Open menu') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p class="hidden text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400 sm:block">BookResa Admin</p>
                            <h1 class="truncate text-base font-bold tracking-tight text-slate-950 dark:text-white sm:text-lg">@yield('heading', __('Admin'))</h1>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <x-locale-switcher />
                        <x-theme-toggle />
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
