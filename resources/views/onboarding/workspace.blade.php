<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <x-seo
        :title="__('app.configure_workspace').' — '.config('bookresa.name', 'BookResa')"
        :description="__('app.configure_workspace_message')"
        robots="noindex,nofollow,noarchive"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-slate-950 antialiased dark:bg-slate-950 dark:text-white">
    @php
        $moduleStepIndex = collect($steps)->search(static fn (array $step): bool => $step['key'] === 'modules');
        $enabledModuleCount = $tenant->modules->filter(
            static fn ($tenantModule): bool => (bool) data_get($tenantModule->pivot, 'enabled', false)
        )->count();
        $businessName = data_get($tenant->profile?->name, app()->getLocale())
            ?? data_get($tenant->profile?->name, 'en')
            ?? $tenant->slug;
    @endphp

    <div class="min-h-screen">
        <header class="border-b border-slate-200/80 dark:border-slate-800">
            <div class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-5 sm:px-8">
                <a href="{{ route('onboarding.workspace') }}" aria-label="BookResa">
                    <img src="{{ asset('logo.png') }}"
                         alt="BookResa"
                         width="707"
                         height="353"
                         decoding="async"
                         class="h-9 w-auto object-contain dark:hidden">
                    <img src="{{ asset('logodark.png') }}"
                         alt="BookResa"
                         width="707"
                         height="353"
                         decoding="async"
                         class="hidden h-9 w-auto object-contain dark:block">
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="hidden max-w-44 truncate text-xs font-bold text-slate-400 sm:block">
                        {{ $businessName }}
                    </span>
                    <x-locale-switcher compact />
                    <x-theme-toggle compact />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="hidden rounded-lg px-2.5 py-2 text-xs font-bold text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 sm:block dark:hover:bg-slate-900 dark:hover:text-white">
                            {{ __('app.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-5 py-8 sm:px-8 sm:py-10 lg:py-14">
            <div class="mx-auto max-w-5xl">
                <div class="flex flex-col gap-6 border-b border-slate-200 pb-7 dark:border-slate-800 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                            <span>{{ __('app.setup_progress') }}</span>
                            <span aria-hidden="true">·</span>
                            <span class="text-brand-indigo">{{ __('app.step_progress', ['current' => $moduleStepIndex + 1, 'total' => count($steps)]) }}</span>
                        </div>

                        <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                            {{ __('app.choose_modules') }}
                        </h1>

                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-base">
                            {{ __('app.choose_modules_message') }}
                        </p>
                    </div>

                    <div class="shrink-0">
                        <div class="text-end text-2xl font-black text-slate-950 dark:text-white">
                            {{ $enabledModuleCount }}
                        </div>
                        <div class="mt-0.5 text-end text-xs font-semibold text-slate-400">
                            {{ __('app.modules_enabled') }}
                        </div>
                    </div>
                </div>

                <div class="mt-7 flex items-center gap-2">
                    @foreach ($steps as $step)
                        @php
                            $isCurrent = $step['key'] === 'modules';
                        @endphp
                        <div class="h-1.5 flex-1 rounded-full {{ $step['complete'] ? 'bg-emerald-500' : ($isCurrent ? 'bg-brand-indigo' : 'bg-slate-200 dark:bg-slate-800') }}"
                             title="{{ $step['label'] }}"
                             aria-label="{{ $step['label'] }}"></div>
                    @endforeach
                </div>

                <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-start">
                    <form method="POST" action="{{ route('onboarding.workspace.modules') }}">
                        @csrf

                        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                            @foreach ($modules as $index => $module)
                                @php
                                    $enabled = $tenant->modules->contains(
                                        static fn ($tenantModule): bool =>
                                            $tenantModule->getKey() === $module->getKey()
                                            && (bool) data_get($tenantModule->pivot, 'enabled', false)
                                    );
                                    $isCore = $module->is_core;
                                    $entitled = $isCore || $entitledModuleKeys->contains($module->key);
                                    $locked = ! $isCore && ! $entitled;
                                @endphp

                                <label class="group flex min-h-[84px] cursor-pointer items-center gap-4 px-4 py-4 sm:px-5
                                    {{ $index > 0 ? 'border-t border-slate-200 dark:border-slate-800' : '' }}
                                    {{ $locked ? 'bg-slate-50/60 dark:bg-slate-900/40' : 'bg-white hover:bg-slate-50/80 dark:bg-slate-950 dark:hover:bg-slate-900' }}">
                                    <input
                                        type="checkbox"
                                        name="module_ids[]"
                                        value="{{ $module->id }}"
                                        @checked($enabled)
                                        @disabled($isCore || $locked)
                                        class="peer sr-only"
                                    >

                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                        {{ $locked
                                            ? 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
                                            : 'bg-indigo-50 text-brand-indigo dark:bg-indigo-950/50 dark:text-indigo-300' }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            @if ($module->key === 'calendar' || $module->key === 'appointments')
                                                <rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M8 3v4M16 3v4M4 9h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            @elseif ($module->key === 'customers')
                                                <circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M3.5 20c.6-3.3 2.4-5 5.5-5s4.9 1.7 5.5 5M17 11a2.5 2.5 0 1 0 0-5M16.5 15c2.5.4 3.8 2 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            @elseif ($module->key === 'services')
                                                <path d="M8 4h8l1 4-3.5 12h-5L5 8l1-4h2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                <path d="M8 8h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            @elseif ($module->key === 'staff')
                                                <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            @elseif ($module->key === 'notifications')
                                                <path d="M18 9a6 6 0 1 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            @elseif ($module->key === 'payments')
                                                <rect x="3.5" y="5" width="17" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M3.5 10h17M7 15h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            @elseif ($module->key === 'invoices')
                                                <path d="M7 3h10v18l-3-2-2 2-2-2-3 2V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                <path d="M9 8h6M9 12h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            @elseif ($module->key === 'inventory')
                                                <path d="m4 8 8-4 8 4-8 4-8-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                <path d="M4 8v8l8 4 8-4V8M12 12v8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            @elseif ($module->key === 'branches')
                                                <path d="M4 20V6l8-3 8 3v14M8 20v-4h8v4M8 9h2M14 9h2M8 12h2M14 12h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            @else
                                                <rect x="5" y="5" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                            @endif
                                        </svg>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-extrabold text-slate-900 dark:text-white">
                                                {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                            </span>

                                            @if ($isCore)
                                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                    {{ __('app.core') }}
                                                </span>
                                            @elseif ($locked)
                                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                    {{ __('app.upgrade') }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="mt-1 block truncate text-xs leading-5 text-slate-500 dark:text-slate-400">
                                            @if ($module->description)
                                                {{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}
                                            @else
                                                {{ $isCore ? __('app.module_core_help') : __('app.module_optional_help') }}
                                            @endif
                                        </span>
                                    </span>

                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border-2
                                        {{ $locked
                                            ? 'border-slate-200 text-transparent dark:border-slate-700'
                                            : 'border-slate-300 bg-white text-transparent peer-checked:border-brand-indigo peer-checked:bg-brand-indigo peer-checked:text-white dark:border-slate-600 dark:bg-slate-900' }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('module_ids')
                            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                                {{ $message }}
                            </div>
                        @enderror

                        @if (! $hasSubscription)
                            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                                {{ __('app.optional_modules_subscription_message') }}
                            </div>
                        @endif

                        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-2 text-xs font-semibold leading-5 text-slate-400 dark:text-slate-500">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>{{ __('app.core_modules_stay_enabled') }}</span>
                            </div>

                            <button type="submit"
                                    class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#253554] sm:w-auto dark:bg-brand-indigo dark:hover:bg-indigo-500">
                                {{ __('app.save_modules_continue') }}
                                <svg class="h-4 w-4 br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <aside class="order-first lg:order-none">
                        <div class="border-s-2 border-brand-indigo ps-4">
                            <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-brand-indigo">
                                {{ __('app.workspace') }}
                            </p>
                            <p class="mt-2 text-base font-black text-slate-900 dark:text-white">
                                {{ $businessName }}
                            </p>
                            <p class="mt-2 text-xs leading-6 text-slate-500 dark:text-slate-400">
                                {{ __('app.workspace_tip_text') }}
                            </p>
                        </div>

                        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
                            <p class="text-xs font-extrabold text-slate-500 dark:text-slate-400">
                                {{ __('app.setup_progress') }}
                            </p>

                            <div class="mt-4 space-y-4">
                                @foreach ($steps as $step)
                                    @php
                                        $isCurrent = $step['key'] === 'modules';
                                    @endphp
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-black
                                            {{ $step['complete']
                                                ? 'bg-emerald-500 text-white'
                                                : ($isCurrent
                                                    ? 'bg-brand-indigo text-white'
                                                    : 'bg-slate-100 text-slate-400 dark:bg-slate-900 dark:text-slate-600') }}">
                                            @if ($step['complete'])
                                                ✓
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </span>
                                        <span class="text-xs font-bold {{ $isCurrent ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                                            {{ $step['label'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
