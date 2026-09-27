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

    <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-5 sm:px-6 lg:px-8">
        <header class="flex items-center justify-between gap-4">
            <a href="{{ route('onboarding.workspace') }}" class="shrink-0" aria-label="BookResa">
                <img src="{{ asset('logo.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     class="h-9 w-auto object-contain sm:h-10 dark:hidden">
                <img src="{{ asset('logodark.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     class="hidden h-9 w-auto object-contain sm:h-10 dark:block">
            </a>

            <div class="flex items-center gap-2">
                <x-locale-switcher compact />
                <x-theme-toggle compact />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="hidden min-h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 sm:inline-flex dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                        {{ __('app.logout') }}
                    </button>
                </form>
            </div>
        </header>

        <section class="mt-8 flex-1 pb-8 lg:mt-10 lg:pb-12">
            <div class="mx-auto max-w-5xl">
                <div class="text-center">
                    <span class="inline-flex items-center rounded-full bg-brand-indigo/10 px-3 py-1 text-xs font-extrabold text-brand-indigo dark:bg-brand-indigo/15 dark:text-indigo-300">
                        {{ __('app.step_progress', ['current' => $moduleStepIndex + 1, 'total' => count($steps)]) }}
                    </span>

                    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                        {{ __('app.configure_workspace') }}
                    </h1>

                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-base">
                        {{ __('app.configure_workspace_message') }}
                    </p>
                </div>

                <div class="mt-8 rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-[0_18px_55px_rgba(15,23,42,0.06)] backdrop-blur dark:border-slate-800 dark:bg-slate-900/85 dark:shadow-black/20 sm:p-4">
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                        @foreach ($steps as $step)
                            @php
                                $isCurrent = $step['key'] === 'modules';
                                $isComplete = $step['complete'];
                            @endphp

                            <div class="relative rounded-2xl px-2 py-2.5 text-center sm:px-3 sm:py-3 {{ $isCurrent ? 'bg-brand-indigo/10 dark:bg-brand-indigo/12' : '' }}">
                                <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full text-xs font-black
                                    {{ $isComplete
                                        ? 'bg-emerald-500 text-white'
                                        : ($isCurrent
                                            ? 'bg-brand-indigo text-white shadow-sm'
                                            : 'border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-500') }}">
                                    @if ($isComplete)
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </div>

                                <p class="mt-2 truncate text-xs font-extrabold {{ $isCurrent ? 'text-brand-indigo dark:text-indigo-300' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $step['label'] }}
                                </p>

                                <p class="mt-0.5 hidden text-[10px] font-semibold sm:block {{ $isCurrent ? 'text-brand-indigo/70 dark:text-indigo-300/70' : 'text-slate-400 dark:text-slate-500' }}">
                                    {{ $isComplete ? __('app.complete') : ($isCurrent ? __('app.current_step') : __('app.locked')) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_22px_60px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                    <div class="border-b border-slate-200 px-5 py-6 sm:px-8 sm:py-7 dark:border-slate-800">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                            <div class="max-w-2xl">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-brand-indigo">
                                        {{ __('app.workspace_modules') }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-extrabold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        {{ $enabledModuleCount }} {{ __('app.modules_enabled') }}
                                    </span>
                                </div>

                                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                                    {{ __('app.choose_modules') }}
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    {{ __('app.choose_modules_message') }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950/70">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-400">
                                    {{ __('app.workspace') }}
                                </p>
                                <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">
                                    {{ data_get($tenant->profile?->name, app()->getLocale()) ?? data_get($tenant->profile?->name, 'en') ?? $tenant->slug }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="px-5 py-5 sm:px-8 sm:py-8">
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

                                <label class="group relative flex min-h-[118px] cursor-pointer gap-4 rounded-2xl border p-4 transition
                                    {{ $locked
                                        ? 'cursor-not-allowed border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-950/50'
                                        : 'border-slate-200 bg-white hover:-translate-y-0.5 hover:border-brand-indigo/40 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-indigo/40' }}">
                                    <input
                                        type="checkbox"
                                        name="module_ids[]"
                                        value="{{ $module->id }}"
                                        @checked($enabled)
                                        @disabled($isCore || $locked)
                                        class="peer sr-only"
                                    >

                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl
                                        {{ $isCore
                                            ? 'bg-brand-indigo/10 text-brand-indigo dark:bg-brand-indigo/15 dark:text-indigo-300'
                                            : ($locked
                                                ? 'bg-slate-200 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
                                                : 'bg-brand-coral/10 text-brand-coral') }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <rect x="4" y="4" width="6" height="6" rx="1.4" stroke="currentColor" stroke-width="1.8"/>
                                            <rect x="14" y="4" width="6" height="6" rx="1.4" stroke="currentColor" stroke-width="1.8"/>
                                            <rect x="4" y="14" width="6" height="6" rx="1.4" stroke="currentColor" stroke-width="1.8"/>
                                            <rect x="14" y="14" width="6" height="6" rx="1.4" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                    </span>

                                    <span class="min-w-0 flex-1 pe-7">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-black text-slate-950 dark:text-white">
                                                {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                            </span>

                                            @if ($isCore)
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                    {{ __('app.core') }}
                                                </span>
                                            @elseif ($locked)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                                                    {{ __('app.upgrade') }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="mt-2 block text-xs leading-5 text-slate-500 dark:text-slate-400">
                                            @if ($module->description)
                                                {{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}
                                            @else
                                                {{ $isCore ? __('app.module_core_help') : __('app.module_optional_help') }}
                                            @endif
                                        </span>
                                    </span>

                                    <span class="absolute end-4 top-4 flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-transparent text-transparent transition
                                        peer-checked:border-brand-indigo peer-checked:bg-brand-indigo peer-checked:text-white
                                        {{ $locked ? 'dark:border-slate-700' : 'group-hover:border-brand-indigo dark:border-slate-600' }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('module_ids')
                            <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                                {{ $message }}
                            </div>
                        @enderror

                        @if (! $hasSubscription)
                            <div class="mt-5 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-4 dark:border-amber-900/70 dark:bg-amber-950/20">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 8v5m0 3.25h.01M10.3 4.7l-7 12.1A1.5 1.5 0 0 0 4.6 19h14.8a1.5 1.5 0 0 0 1.3-2.2l-7-12.1a1.95 1.95 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-amber-900 dark:text-amber-200">{{ __('app.no_subscription_yet') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-amber-800/80 dark:text-amber-200/80">
                                        {{ __('app.optional_modules_subscription_message') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-7 flex flex-col gap-4 border-t border-slate-100 pt-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 dark:text-slate-500">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span>{{ __('app.core_modules_stay_enabled') }}</span>
                            </div>

                            <button type="submit"
                                    class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white shadow-sm transition hover:-translate-y-px hover:shadow-lg sm:w-auto dark:bg-brand-indigo">
                                {{ __('app.save_modules_continue') }}
                                <svg class="h-4 w-4 br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>

                <p class="mt-5 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                    {{ __('app.workspace_tip_text') }}
                </p>
            </div>
        </section>

        <footer class="pb-2 text-center text-xs text-slate-400 dark:text-slate-500">
            © {{ now()->year }} BookResa
        </footer>
    </main>
</body>
</html>
