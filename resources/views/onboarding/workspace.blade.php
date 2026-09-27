<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E2A44">
    <x-seo
        :title="__('app.configure_workspace').' — '.config('bookresa.name', 'BookResa')"
        :description="__('app.configure_workspace_message')"
        robots="noindex,nofollow,noarchive"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="br-shell min-h-screen antialiased">
    @php
        $moduleStepIndex = collect($steps)->search(static fn (array $step): bool => $step['key'] === 'modules');
        $enabledModuleCount = $tenant->modules->filter(
            static fn ($tenantModule): bool => (bool) data_get($tenantModule->pivot, 'enabled', false)
        )->count();
    @endphp

    <main class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <header class="flex items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('onboarding.workspace') }}" class="shrink-0" aria-label="BookResa">
                    <img src="{{ asset('logo.png') }}"
                         alt="BookResa"
                         width="707"
                         height="353"
                         decoding="async"
                         class="h-10 w-auto object-contain sm:h-11 dark:hidden">
                    <img src="{{ asset('logodark.png') }}"
                         alt="BookResa"
                         width="707"
                         height="353"
                         decoding="async"
                         class="hidden h-10 w-auto object-contain sm:h-11 dark:block">
                </a>
                <span class="hidden h-6 w-px bg-slate-200 dark:bg-slate-800 sm:block" aria-hidden="true"></span>
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">{{ __('app.workspace') }}</p>
                    <p class="truncate text-sm font-extrabold text-slate-900 dark:text-white">
                        {{ data_get($tenant->profile?->name, app()->getLocale()) ?? data_get($tenant->profile?->name, 'en') ?? $tenant->slug }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <div class="hidden sm:block">
                    <x-locale-switcher compact />
                </div>
                <x-theme-toggle compact />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 sm:inline-flex dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                        {{ __('app.logout') }}
                    </button>
                </form>
            </div>
        </header>

        <section class="mt-8 lg:mt-10">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-brand-indigo/10 px-3 py-1 text-xs font-bold text-brand-indigo dark:bg-brand-indigo/15 dark:text-indigo-300">
                            {{ __('app.step_progress', ['current' => $moduleStepIndex + 1, 'total' => count($steps)]) }}
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                            {{ $enabledModuleCount }} {{ __('app.modules_enabled') }}
                        </span>
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                        {{ __('app.configure_workspace') }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-base">
                        {{ __('app.configure_workspace_message') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">{{ __('app.next_step') }}</p>
                    <div class="mt-1 flex items-center gap-2 text-sm font-extrabold text-slate-900 dark:text-white">
                        <span>{{ __('app.onboarding_steps.services') }}</span>
                        <svg class="h-4 w-4 text-brand-indigo br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="mt-6 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" aria-hidden="true">
                <div class="h-full rounded-full bg-gradient-to-r from-brand-indigo to-violet-500 transition-all"
                     style="width: {{ (($moduleStepIndex + 1) / count($steps)) * 100 }}%"></div>
            </div>
        </section>

        <div class="mt-8 flex flex-col gap-6 lg:flex-row lg:items-start">
            <section class="min-w-0 flex-1">
                <div class="br-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-5 py-5 sm:px-7 sm:py-6 dark:border-slate-800">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-brand-indigo">{{ __('app.workspace_modules') }}</p>
                                <h2 class="mt-1.5 text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                                    {{ __('app.choose_modules') }}
                                </h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    {{ __('app.choose_modules_message') }}
                                </p>
                            </div>

                            <div class="shrink-0 rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                                {{ $tenant->slug }}
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="px-5 py-5 sm:px-7 sm:py-7">
                        @csrf

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($modules as $module)
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

                                <label class="group relative flex min-h-[126px] gap-3.5 rounded-2xl border p-4 transition
                                    {{ $locked
                                        ? 'border-slate-200 bg-slate-50/70 opacity-80 dark:border-slate-800 dark:bg-slate-950/50'
                                        : 'border-slate-200 bg-white hover:-translate-y-0.5 hover:border-brand-indigo/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-indigo/40' }}
                                    {{ $isCore || $entitled ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                        {{ $isCore
                                            ? 'bg-brand-indigo/10 text-brand-indigo dark:bg-brand-indigo/15 dark:text-indigo-300'
                                            : ($locked
                                                ? 'bg-slate-200 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
                                                : 'bg-brand-coral/10 text-brand-coral') }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <rect x="4" y="4" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                            <rect x="14" y="4" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                            <rect x="4" y="14" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                            <rect x="14" y="14" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                    </span>

                                    <input
                                        type="checkbox"
                                        name="module_ids[]"
                                        value="{{ $module->id }}"
                                        @checked($enabled)
                                        @disabled($isCore || $locked)
                                        class="sr-only peer"
                                    >

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-extrabold text-slate-900 dark:text-white">
                                                {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                            </span>

                                            @if ($isCore)
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                    {{ __('app.core') }}
                                                </span>
                                            @elseif ($locked)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                                                    {{ __('app.upgrade') }}
                                                </span>
                                            @endif
                                        </span>

                                        @if ($module->description)
                                            <span class="mt-2 block text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                {{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}
                                            </span>
                                        @else
                                            <span class="mt-2 block text-xs leading-5 text-slate-400 dark:text-slate-500">
                                                {{ $isCore ? __('app.module_core_help') : __('app.module_optional_help') }}
                                            </span>
                                        @endif
                                    </span>

                                    <span class="absolute end-4 top-4 flex h-5 w-5 items-center justify-center rounded-full border-2 border-slate-300 bg-transparent text-transparent transition
                                        peer-checked:border-brand-indigo peer-checked:bg-brand-indigo peer-checked:text-white
                                        {{ $locked ? 'dark:border-slate-700' : 'group-hover:border-brand-indigo dark:border-slate-600' }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('module_ids')
                            <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
                                {{ $message }}
                            </p>
                        @enderror

                        @if (! $hasSubscription)
                            <div class="mt-5 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-4 dark:border-amber-900/70 dark:bg-amber-950/20">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 8v5m0 3.25h.01M10.3 4.7l-7 12.1A1.5 1.5 0 0 0 4.6 19h14.8a1.5 1.5 0 0 0 1.3-2.2l-7-12.1a1.95 1.95 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-amber-900 dark:text-amber-200">{{ __('app.no_subscription_yet') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-amber-800/80 dark:text-amber-200/80">
                                        {{ __('app.optional_modules_subscription_message') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                            <p class="text-xs leading-5 text-slate-400 dark:text-slate-500">
                                {{ __('app.core_modules_stay_enabled') }}
                            </p>

                            <button type="submit"
                                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-navy px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md dark:bg-brand-indigo">
                                {{ __('app.save_modules_continue') }}
                                <svg class="h-4 w-4 br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <aside class="w-full shrink-0 lg:w-[292px]">
                <div class="br-panel p-4 sm:p-5 lg:sticky lg:top-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">{{ __('app.setup_progress') }}</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950 dark:text-white">{{ __('app.your_setup') }}</h2>
                    </div>

                    <div class="mt-5 space-y-1">
                        @foreach ($steps as $step)
                            @php
                                $isCurrent = $step['key'] === 'modules';
                                $isPast = $loop->index < $moduleStepIndex;
                                $isFuture = $loop->index > $moduleStepIndex;
                            @endphp

                            <div class="relative flex gap-3 rounded-2xl px-3 py-3
                                {{ $isCurrent
                                    ? 'bg-brand-indigo/10 dark:bg-brand-indigo/10'
                                    : 'bg-transparent' }}">
                                <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black
                                    {{ $isPast
                                        ? 'bg-emerald-500 text-white'
                                        : ($isCurrent
                                            ? 'bg-brand-indigo text-white shadow-sm'
                                            : 'border border-slate-200 bg-white text-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-500') }}">
                                    @if ($isPast)
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>

                                @if (! $loop->last)
                                    <span class="absolute start-[1.18rem] top-11 h-7 w-px {{ $isPast ? 'bg-emerald-300 dark:bg-emerald-900' : 'bg-slate-200 dark:bg-slate-800' }}" aria-hidden="true"></span>
                                @endif

                                <div class="min-w-0 pt-0.5">
                                    <p class="text-sm font-extrabold {{ $isCurrent ? 'text-brand-indigo dark:text-indigo-300' : 'text-slate-900 dark:text-white' }}">
                                        {{ $step['label'] }}
                                    </p>
                                    <p class="mt-0.5 text-xs font-semibold {{ $isPast ? 'text-emerald-600 dark:text-emerald-400' : ($isCurrent ? 'text-brand-indigo/80 dark:text-indigo-300/80' : 'text-slate-400 dark:text-slate-500') }}">
                                        {{ $isPast ? __('app.complete') : ($isCurrent ? __('app.current_step') : __('app.locked')) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('app.workspace_tip_title') }}</p>
                        <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-500">
                            {{ __('app.workspace_tip_text') }}
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>
