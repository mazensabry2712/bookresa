<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">
    <title>@yield('title', config('app.name', 'BookResa'))</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="br-shell antialiased">
    <div class="br-drawer-backdrop lg:hidden" data-bookresa-sidebar-backdrop aria-hidden="true"></div>

    <div class="min-h-screen lg:flex">
        <aside id="bookresa-sidebar"
               class="br-sidebar br-drawer pointer-events-auto lg:static lg:z-auto lg:flex lg:flex-col lg:shrink-0 lg:transform-none"
               data-bookresa-sidebar
               aria-hidden="false"
               aria-label="{{ __('app.workspace') }}">
            <div class="br-sidebar-top flex min-h-16 items-center gap-2 border-b border-slate-200 px-3 dark:border-slate-800 sm:px-4">
                <a href="{{ route('dashboard', ['tenant' => $tenant->slug]) }}"
                   class="br-sidebar-brand flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1.5"
                   aria-label="BookResa">
                    <span class="br-sidebar-logo flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-soft dark:bg-slate-800">
                        <img src="{{ asset('logo.png') }}" alt="BookResa" class="h-full w-full object-contain p-0.5 dark:hidden">
                        <img src="{{ asset('logodark.png') }}" alt="BookResa" class="hidden h-full w-full object-contain p-0.5 dark:block">
                    </span>
                    <span class="br-sidebar-label min-w-0">
                        <span class="block truncate text-sm font-extrabold tracking-tight text-slate-950 dark:text-white">BookResa</span>
                        <span class="block truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">
                            {{ data_get($tenant->profile?->name, app()->getLocale()) ?? data_get($tenant->profile?->name, 'en') ?? $tenant->slug }}
                        </span>
                    </span>
                </a>

                <button type="button"
                        class="br-sidebar-collapse hidden shrink-0 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white lg:inline-flex"
                        data-bookresa-sidebar-collapse
                        data-label-collapse="{{ __('app.collapse_sidebar') }}"
                        data-label-expand="{{ __('app.expand_sidebar') }}"
                        aria-expanded="true"
                        aria-controls="bookresa-sidebar"
                        aria-label="{{ __('app.collapse_sidebar') }}"
                        title="{{ __('app.collapse_sidebar') }}">
                    <svg class="h-4.5 w-4.5 rtl-flip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6"/>
                    </svg>
                </button>

                <button type="button"
                        class="ml-auto rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden"
                        data-bookresa-sidebar-close
                        aria-label="{{ __('app.close') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="br-sidebar-workspace px-2 pt-3 sm:px-3">
                <x-workspace-switcher :tenant="$tenant" />
            </div>

            <div class="flex-1 overflow-y-auto px-2 py-4 sm:px-3">
                <nav class="relative z-10 space-y-5 pointer-events-auto" aria-label="{{ __('app.workspace') }}">
                    <section>
                        <p class="br-sidebar-section-label px-3 pb-2 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('app.overview') }}</p>
                        <div class="space-y-1">
                            <a href="{{ route('dashboard', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('dashboard') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.dashboard') }}">
                                <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-12h6V4h-6v4Z"/></svg></span>
                                <span class="br-sidebar-label">{{ __('app.dashboard') }}</span>
                            </a>
                            @can('calendar.view')
                                <a href="{{ route('calendar.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('calendar.index') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.calendar') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3.5 10h17M8 14h2M14 14h2M8 18h2M14 18h2"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.calendar') }}</span>
                                </a>
                            @endcan
                        </div>
                    </section>

                    <section>
                        <p class="br-sidebar-section-label px-3 pb-2 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('app.operations') }}</p>
                        <div class="space-y-1">
                            @can('bookings.view')
                                <a href="{{ route('booking.management.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('booking.management.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.bookings') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3.5 10h17"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.bookings') }}</span>
                                </a>
                            @endcan
                            @can('calendar.view')
                                <a href="{{ route('scheduling.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('scheduling.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.scheduling') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.06.06-1.7 1.7-.06-.06a1.8 1.8 0 0 0-1.98-.36 1.8 1.8 0 0 0-1.08 1.65V20h-2.4v-.03a1.8 1.8 0 0 0-1.08-1.65 1.8 1.8 0 0 0-1.98.36l-.06.06-1.7-1.7.06-.06A1.8 1.8 0 0 0 8.2 15a1.8 1.8 0 0 0-1.65-1.08H6v-2.4h.55A1.8 1.8 0 0 0 8.2 10a1.8 1.8 0 0 0-.36-1.98l-.06-.06 1.7-1.7.06.06a1.8 1.8 0 0 0 1.08.36 1.8 1.8 0 0 0 1.08-1.65V5h2.4v.03a1.8 1.8 0 0 0 1.08 1.65 1.8 1.8 0 0 0 1.08.36 1.8 1.8 0 0 0 1.98-.36l.06-.06 1.7 1.7-.06.06A1.8 1.8 0 0 0 19.4 10c.24.55.72.92 1.32.92H21v2.4h-.28c-.6 0-1.08.36-1.32.92Z"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.scheduling') }}</span>
                                </a>
                            @endcan
                            @can('services.view')
                                <a href="{{ route('services.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('services.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.services') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M6 4.5h12M6 9h12M6 13.5h8M6 18h6"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.services') }}</span>
                                </a>
                            @endcan
                            @can('staff.view')
                                <a href="{{ route('staff.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('staff.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.staff') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path stroke-linecap="round" d="M6 20a6 6 0 0 1 12 0"/><path stroke-linecap="round" d="M4 11a3 3 0 0 1 2.5-2.95M20 11a3 3 0 0 0-2.5-2.95"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.staff') }}</span>
                                </a>
                            @endcan
                            @can('customers.view')
                                <a href="{{ route('customers.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('customers.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.customers') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path stroke-linecap="round" d="M3.5 20a5.5 5.5 0 0 1 11 0M16 7.5a3 3 0 0 1 0 5.8M16 15.5a4.5 4.5 0 0 1 4.5 4.5"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.customers') }}</span>
                                </a>
                            @endcan
                        </div>
                    </section>

                    <section>
                        <p class="br-sidebar-section-label px-3 pb-2 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('app.finance') }}</p>
                        <div class="space-y-1">
                            @can('billing.view')
                                @if ($tenant->modules->contains('key', 'payments'))
                                    <a href="{{ route('payments.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('payments.index') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.payments') }}">
                                        <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" d="M3 10h18M7 15h4"/></svg></span>
                                        <span class="br-sidebar-label">{{ __('app.payments') }}</span>
                                    </a>
                                @endif
                                <a href="{{ route('billing.subscription', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('billing.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.billing') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" d="M7 15h5M7 9h10"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.billing') }}</span>
                                </a>
                            @endcan
                        </div>
                    </section>

                    <section>
                        <p class="br-sidebar-section-label px-3 pb-2 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('app.insights') }}</p>
                        <div class="space-y-1">
                            @can('reports.view')
                                <a href="{{ route('reports.business', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('reports.business') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.reports') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M5 19V9M12 19V5M19 19v-7M3 19h18"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.reports') }}</span>
                                </a>
                            @endcan
                            @can('notifications.view')
                                <a href="{{ route('notifications.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('notifications.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.notification_ui.notifications') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.notification_ui.notifications') }}</span>
                                </a>
                            @endcan
                        </div>
                    </section>

                    <section>
                        <p class="br-sidebar-section-label px-3 pb-2 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('app.settings') }}</p>
                        <div class="space-y-1">
                            @can('business.view')
                                <a href="{{ route('business.profile.edit', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('business.profile.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.business') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M19 13.2a1.8 1.8 0 0 0 .3 1.95l.05.05-1.7 1.7-.05-.05a1.8 1.8 0 0 0-1.95-.3 1.8 1.8 0 0 0-1.25 1.7V20h-2.4v-.05a1.8 1.8 0 0 0-1.25-1.7 1.8 1.8 0 0 0-1.95.3l-.05.05-1.7-1.7.05-.05A1.8 1.8 0 0 0 6 13.2a1.8 1.8 0 0 0-1.7-1.25H4v-2.4h.3A1.8 1.8 0 0 0 6 8.3a1.8 1.8 0 0 0-.3-1.95l-.05-.05 1.7-1.7.05.05A1.8 1.8 0 0 0 9.35 5a1.8 1.8 0 0 0 1.25-1.7V3h2.4v.3A1.8 1.8 0 0 0 14.25 5a1.8 1.8 0 0 0 1.95-.3l-.05-.05 1.7 1.7-.05.05A1.8 1.8 0 0 0 17.7 8.3c.2.47.63.8 1.15.8H20v2.4h-.3c-.5 0-.95.32-1.15.8Z"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.business') }}</span>
                                </a>
                            @endcan
                            @can('settings.manage')
                                <a href="{{ route('business.modules.index', ['tenant' => $tenant->slug]) }}" data-active="{{ request()->routeIs('business.modules.*') ? 'true' : 'false' }}" class="br-nav-link" title="{{ __('app.modules') }}">
                                    <span class="br-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 5h16M4 9h16M4 13h10M4 17h7"/></svg></span>
                                    <span class="br-sidebar-label">{{ __('app.modules') }}</span>
                                </a>
                            @endcan
                        </div>
                    </section>
                </nav>
            </div>

            @php
                $userName = (string) auth()->user()->name;
                $userInitials = str($userName)->trim()->explode(' ')->filter()->take(2)->map(fn ($part) => str($part)->substr(0, 1)->upper())->implode('');
            @endphp
            <div class="border-t border-slate-200 p-2 dark:border-slate-800 sm:p-3">
                <div class="relative" data-user-menu>
                    <button type="button"
                            class="flex w-full items-center gap-3 rounded-xl br-surface-soft px-2.5 py-2.5 text-start transition hover:bg-white dark:hover:bg-slate-800"
                            data-user-menu-toggle
                            aria-expanded="false"
                            aria-haspopup="true"
                            title="{{ __('app.user_menu') }}">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-navy text-xs font-extrabold text-white dark:bg-indigo-500">{{ $userInitials }}</span>
                        <span class="br-sidebar-label min-w-0 flex-1">
                            <span class="block truncate text-xs font-bold text-slate-900 dark:text-white">{{ $userName }}</span>
                            <span class="mt-0.5 block truncate text-[11px] text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</span>
                        </span>
                        <svg class="br-sidebar-label h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m7 10 5 5 5-5"/></svg>
                    </button>

                    <div class="absolute bottom-full inset-inline-start-0 z-[80] mb-2 hidden w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900"
                         data-user-menu-panel>
                        <div class="px-3 py-2">
                            <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $userName }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="my-1 h-px bg-slate-100 dark:bg-slate-800"></div>
                        @can('business.view')
                            <a href="{{ route('business.profile.edit', ['tenant' => $tenant->slug]) }}" class="br-menu-item">{{ __('app.workspace_settings') }}</a>
                        @endcan
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="br-menu-item w-full text-start">{{ __('app.logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 backdrop-blur dark:border-slate-800/90 dark:bg-slate-900/95">
                <div class="flex min-h-16 items-center justify-between gap-3 px-3 sm:px-5 lg:px-8">
                    <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                        <button type="button"
                                class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 lg:hidden"
                                data-bookresa-sidebar-toggle
                                aria-expanded="false"
                                aria-controls="bookresa-sidebar"
                                aria-label="{{ __('app.open_menu') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p class="hidden text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400 sm:block">{{ $tenant->slug }}</p>
                            <h1 class="truncate text-base font-bold tracking-tight text-slate-950 dark:text-white sm:text-lg">@yield('heading', __('app.dashboard'))</h1>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                        @can('notifications.view')
                            <a href="{{ route('notifications.index', ['tenant' => $tenant->slug]) }}"
                               class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                               aria-label="{{ __('app.notification_ui.notifications') }}"
                               title="{{ __('app.notification_ui.notifications') }}">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>
                                </svg>
                            </a>
                        @endcan
                        <x-locale-switcher />
                        <x-theme-toggle />
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1440px] px-3 py-5 sm:px-5 sm:py-6 lg:px-8 lg:py-8">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
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