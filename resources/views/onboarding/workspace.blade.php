<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8fc">
    <x-seo
        :title="__('app.choose_modules').' — '.config('bookresa.name', 'BookResa')"
        :description="__('app.choose_modules_message')"
        robots="noindex,nofollow,noarchive"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f4f6fb] text-slate-950 antialiased dark:bg-[#070b14] dark:text-white">
    @php
        $moduleStepIndex = collect($steps)->search(static fn (array $step): bool => $step['key'] === 'modules');
        $coreModules = $modules->filter(static fn ($module): bool => $module->is_core)->values();
        $optionalModules = $modules->filter(static fn ($module): bool => ! $module->is_core)->values();
        $availableOptionalModules = $optionalModules->filter(
            fn ($module): bool => $entitledModuleKeys->contains($module->key)
        )->values();
        $lockedOptionalModules = $optionalModules->reject(
            fn ($module): bool => $entitledModuleKeys->contains($module->key)
        )->values();
        $enabledModuleCount = $tenant->modules->filter(
            static fn ($tenantModule): bool => (bool) data_get($tenantModule->pivot, 'enabled', false)
        )->count();
        $businessName = data_get($tenant->profile?->name, app()->getLocale())
            ?? data_get($tenant->profile?->name, 'en')
            ?? $tenant->slug;

        $moduleIcon = static function (string $key): string {
            return match ($key) {
                'appointments' => '<rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M4 9h16M8 13h3M8 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M4 9h16M8 13h.01M12 13h.01M16 13h.01M8 16h.01M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                'customers' => '<circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 20c.6-3.3 2.4-5 5.5-5s4.9 1.7 5.5 5M17 11a2.5 2.5 0 1 0 0-5M16.5 15c2.5.4 3.8 2 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                'services' => '<path d="M8 4h8l1 4-3.5 12h-5L5 8l1-4h2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 8h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                'staff' => '<circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                'notifications' => '<path d="M18 9a6 6 0 1 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
                'payments' => '<rect x="3.5" y="5" width="17" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 10h17M7 15h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                'invoices' => '<path d="M7 3h10v18l-3-2-2 2-2-2-3 2V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8h6M9 12h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                'inventory' => '<path d="m4 8 8-4 8 4-8 4-8-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M4 8v8l8 4 8-4V8M12 12v8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
                'branches' => '<path d="M4 20V6l8-3 8 3v14M8 20v-4h8v4M8 9h2M14 9h2M8 12h2M14 12h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
                default => '<rect x="5" y="5" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>',
            };
        };
    @endphp

    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute -top-40 start-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-indigo-200/25 blur-3xl dark:bg-indigo-950/25"></div>

        <header class="relative border-b border-slate-200/80 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
            <div class="mx-auto flex h-[68px] max-w-6xl items-center justify-between px-5 sm:px-8">
                <a href="{{ route('onboarding.workspace') }}" aria-label="BookResa" class="shrink-0">
                    <img src="{{ asset('logo.png') }}" alt="BookResa" width="707" height="353" decoding="async" class="h-8 w-auto object-contain dark:hidden sm:h-9">
                    <img src="{{ asset('logodark.png') }}" alt="BookResa" width="707" height="353" decoding="async" class="hidden h-8 w-auto object-contain dark:block sm:h-9">
                </a>

                <div class="flex items-center gap-1 sm:gap-2">
                    <span class="hidden rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500 md:block dark:bg-slate-900 dark:text-slate-400">
                        {{ $businessName }}
                    </span>
                    <x-locale-switcher compact />
                    <x-theme-toggle compact />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hidden rounded-lg px-2.5 py-2 text-xs font-bold text-slate-400 transition hover:bg-slate-100 hover:text-slate-800 sm:block dark:hover:bg-slate-900 dark:hover:text-white">
                            {{ __('app.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="relative mx-auto w-full max-w-6xl px-5 py-8 pb-12 sm:px-8 sm:py-10 lg:py-14">
            <div class="mx-auto max-w-5xl">
                <div class="flex items-center justify-between gap-4 text-xs font-extrabold">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-brand-navy px-2 text-[10px] text-white dark:bg-brand-indigo">
                            02
                        </span>
                        <span class="text-slate-500 dark:text-slate-400">{{ __('app.step_progress', ['current' => $moduleStepIndex + 1, 'total' => count($steps)]) }}</span>
                    </div>

                    <div class="hidden items-center gap-2 sm:flex">
                        @foreach ($steps as $step)
                            <span class="h-1.5 w-8 rounded-full {{ $step['complete'] ? 'bg-emerald-500' : ($step['key'] === 'modules' ? 'bg-brand-indigo' : 'bg-slate-200 dark:bg-slate-800') }}"></span>
                        @endforeach
                    </div>

                    <span class="text-slate-400">{{ $enabledModuleCount }} {{ __('app.modules_enabled') }}</span>
                </div>

                <section class="relative mt-10 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-950 dark:shadow-black/25">
                    <div class="grid lg:grid-cols-[0.86fr_1.14fr]">
                        <div class="relative overflow-hidden bg-[#182440] px-6 py-8 text-white sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                            <div class="absolute -end-16 -top-16 h-40 w-40 rounded-full border border-white/10"></div>
                            <div class="absolute -end-6 top-12 h-24 w-24 rounded-full border border-white/10"></div>

                            <div class="relative">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10">
                                    <svg class="h-5 w-5 text-indigo-200" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="4" y="4" width="16" height="16" rx="4" stroke="currentColor" stroke-width="1.7"/>
                                        <path d="M8 12h8M12 8v8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </div>

                                <p class="mt-8 text-[11px] font-black uppercase tracking-[0.18em] text-indigo-200/70">
                                    {{ __('app.workspace_modules') }}
                                </p>

                                <h1 class="mt-3 max-w-sm text-3xl font-black leading-tight tracking-[-0.03em] sm:text-4xl">
                                    {{ __('app.setup_workspace_title') }}
                                </h1>

                                <p class="mt-4 max-w-sm text-sm leading-7 text-indigo-100/70">
                                    {{ __('app.setup_workspace_message') }}
                                </p>

                                <div class="mt-9 border-t border-white/10 pt-6">
                                    <p class="text-xs font-bold text-white/50">{{ __('app.workspace') }}</p>
                                    <p class="mt-1 truncate text-sm font-extrabold text-white">{{ $businessName }}</p>
                                </div>

                                <div class="mt-7 flex flex-wrap gap-2">
                                    @foreach ($coreModules as $module)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white/80 ring-1 ring-white/5">
                                            <svg class="h-3 w-3 text-emerald-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                            <div class="flex items-start justify-between gap-5">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.17em] text-brand-indigo">
                                        {{ __('app.choose_modules') }}
                                    </p>
                                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                                        {{ __('app.choose_modules') }}
                                    </h2>
                                    <p class="mt-2 max-w-lg text-sm leading-6 text-slate-500 dark:text-slate-400">
                                        {{ __('app.choose_modules_message') }}
                                    </p>
                                </div>

                                <span class="hidden h-10 min-w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 px-2 text-xs font-black text-slate-500 sm:flex dark:bg-slate-900 dark:text-slate-400">
                                    {{ $enabledModuleCount }}
                                </span>
                            </div>

                            @if ($availableOptionalModules->isNotEmpty())
                                <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="mt-7">
                                    @csrf

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach ($availableOptionalModules as $module)
                                            @php
                                                $enabled = $tenant->modules->contains(
                                                    static fn ($tenantModule): bool =>
                                                        $tenantModule->getKey() === $module->getKey()
                                                        && (bool) data_get($tenantModule->pivot, 'enabled', false)
                                                );
                                            @endphp

                                            <label class="group cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-brand-indigo/40 hover:shadow-[0_12px_30px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-950 dark:hover:border-indigo-500/40">
                                                <input type="checkbox" name="module_ids[]" value="{{ $module->id }}" @checked($enabled) class="peer sr-only">

                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-brand-indigo dark:bg-indigo-950/50 dark:text-indigo-300">
                                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $moduleIcon($module->key) !!}</svg>
                                                    </span>

                                                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-200 transition-colors peer-checked:bg-brand-indigo dark:bg-slate-700 dark:peer-checked:bg-brand-indigo">
                                                        <span class="absolute start-1 h-4 w-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                                                    </span>
                                                </div>

                                                <div class="mt-5">
                                                    <p class="text-sm font-black text-slate-950 dark:text-white">
                                                        {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                                    </p>
                                                    <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                        {{ $module->description ? (data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en')) : __('app.module_descriptions.'.$module->key) }}
                                                    </p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>

                                    @error('module_ids')
                                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                    <div class="mt-7 flex flex-col gap-3 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-xs font-semibold text-slate-400">
                                            {{ __('app.next_step_services') }}
                                        </p>

                                        <button type="submit"
                                                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#263553] sm:w-auto dark:bg-brand-indigo dark:hover:bg-indigo-500">
                                            {{ __('app.save_modules_continue') }}
                                            <svg class="h-4 w-4 br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900/50">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm dark:bg-slate-950 dark:text-slate-300">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                <path d="M12 14v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            </svg>
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">
                                                {{ __('app.more_tools') }}
                                            </p>
                                            <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                                                {{ __('app.unlock_more_tools') }}
                                            </p>
                                            <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                {{ __('app.unlock_more_tools_message') }}
                                            </p>

                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @foreach ($lockedOptionalModules as $module)
                                                    <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-extrabold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-950 dark:text-slate-400 dark:ring-slate-800">
                                                        {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                                        <a href="{{ route('billing.subscription') }}"
                                           class="inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-extrabold text-slate-700 transition hover:border-slate-300 hover:text-slate-950 sm:w-auto dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-slate-600">
                                            {{ __('app.explore_plans') }}
                                        </a>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="mt-6">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#263553] dark:bg-brand-indigo dark:hover:bg-indigo-500">
                                        {{ __('app.save_modules_continue') }}
                                        <svg class="h-4 w-4 br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </section>

                <div class="mt-6 flex items-center justify-center gap-2 text-[11px] font-bold text-slate-400 dark:text-slate-500">
                    <span class="text-brand-indigo">02</span>
                    <span>/</span>
                    <span>06</span>
                    <span class="mx-1 h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                    <span>{{ __('app.next_step_services') }}</span>
                </div>
            </div>
        </main>
    </div>
</body>
</html>