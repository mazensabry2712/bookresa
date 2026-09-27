<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f9fc">
    <x-seo
        :title="__('app.configure_workspace').' — '.config('bookresa.name', 'BookResa')"
        :description="__('app.configure_workspace_message')"
        robots="noindex,nofollow,noarchive"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f7f8fc] text-slate-950 antialiased dark:bg-[#080c16] dark:text-white">
    @php
        $moduleStepIndex = collect($steps)->search(static fn (array $step): bool => $step['key'] === 'modules');
        $coreModules = $modules->filter(static fn ($module): bool => $module->is_core)->values();
        $optionalModules = $modules->filter(static fn ($module): bool => ! $module->is_core)->values();
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

    <div class="min-h-screen">
        <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
            <div class="mx-auto flex h-[68px] max-w-6xl items-center justify-between px-5 sm:px-8">
                <a href="{{ route('onboarding.workspace') }}" aria-label="BookResa">
                    <img src="{{ asset('logo.png') }}"
                         alt="BookResa"
                         width="707"
                         height="353"
                         decoding="async"
                         class="h-8 w-auto object-contain dark:hidden sm:h-9">
                    <img src="{{ asset('logodark.png') }}"
                         alt="BookResa"
                         width="707"
                         height="353"
                         decoding="async"
                         class="hidden h-8 w-auto object-contain dark:block sm:h-9">
                </a>

                <div class="flex items-center gap-1 sm:gap-2">
                    <span class="hidden max-w-48 truncate rounded-lg bg-slate-50 px-2.5 py-2 text-xs font-bold text-slate-500 sm:block dark:bg-slate-900 dark:text-slate-400">
                        {{ $businessName }}
                    </span>
                    <x-locale-switcher compact />
                    <x-theme-toggle compact />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="hidden rounded-lg px-2.5 py-2 text-xs font-bold text-slate-400 transition hover:bg-slate-100 hover:text-slate-800 sm:block dark:hover:bg-slate-900 dark:hover:text-white">
                            {{ __('app.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-5 pb-28 pt-7 sm:px-8 sm:pt-10 lg:pt-12">
            <div class="mx-auto max-w-4xl">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs font-bold">
                        <span class="text-brand-indigo">{{ __('app.step_progress', ['current' => $moduleStepIndex + 1, 'total' => count($steps)]) }}</span>
                        <span class="text-slate-300 dark:text-slate-700">/</span>
                        <span class="text-slate-400">{{ __('app.workspace') }}</span>
                    </div>

                    <span class="text-xs font-bold text-slate-400">
                        {{ $enabledModuleCount }} {{ __('app.modules_enabled') }}
                    </span>
                </div>

                <div class="mt-3 h-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-brand-indigo" style="width: {{ (($moduleStepIndex + 1) / count($steps)) * 100 }}%"></div>
                </div>

                <div class="mt-9">
                    <span class="text-[11px] font-black uppercase tracking-[0.16em] text-brand-indigo">
                        {{ __('app.workspace_modules') }}
                    </span>

                    <h1 class="mt-2 text-3xl font-black tracking-[-0.03em] text-slate-950 dark:text-white sm:text-[42px]">
                        {{ __('app.choose_modules') }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-base">
                        {{ __('app.choose_modules_message') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="mt-9">
                    @csrf

                    <section>
                        <div class="flex items-center justify-between gap-4 px-1">
                            <div>
                                <h2 class="text-sm font-black text-slate-950 dark:text-white">
                                    {{ __('app.core_modules') }}
                                </h2>
                                <p class="mt-1 text-xs font-medium text-slate-400">
                                    {{ __('app.always_on_message') }}
                                </p>
                            </div>

                            <span class="hidden rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black text-emerald-700 sm:inline-flex dark:bg-emerald-950/40 dark:text-emerald-300">
                                {{ __('app.included') }}
                            </span>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($coreModules as $module)
                                <div class="flex min-h-[82px] items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 dark:border-slate-800 dark:bg-slate-950">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-900 dark:text-slate-300">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            {!! $moduleIcon($module->key) !!}
                                        </svg>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-extrabold text-slate-900 dark:text-white">
                                            {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                        </span>
                                        <span class="mt-1 block truncate text-xs font-medium text-slate-400">
                                            @if ($module->description)
                                                {{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}
                                            @else
                                                {{ __('app.module_core_help') }}
                                            @endif
                                        </span>
                                    </span>

                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="mt-10">
                        <div class="flex flex-col gap-2 px-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 class="text-sm font-black text-slate-950 dark:text-white">
                                    {{ __('app.optional_modules') }}
                                </h2>
                                <p class="mt-1 text-xs font-medium text-slate-400">
                                    {{ __('app.optional_modules_help') }}
                                </p>
                            </div>

                            @if (! $hasSubscription)
                                <span class="text-xs font-bold text-slate-400">{{ __('app.no_subscription_yet') }}</span>
                            @endif
                        </div>

                        <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                            @foreach ($optionalModules as $index => $module)
                                @php
                                    $enabled = $tenant->modules->contains(
                                        static fn ($tenantModule): bool =>
                                            $tenantModule->getKey() === $module->getKey()
                                            && (bool) data_get($tenantModule->pivot, 'enabled', false)
                                    );
                                    $entitled = $entitledModuleKeys->contains($module->key);
                                    $locked = ! $entitled;
                                @endphp

                                <label class="group flex min-h-[88px] items-center gap-4 px-4 py-4 sm:px-5
                                    {{ $index > 0 ? 'border-t border-slate-200 dark:border-slate-800' : '' }}
                                    {{ $locked
                                        ? 'cursor-not-allowed bg-slate-50/70 dark:bg-slate-900/40'
                                        : 'cursor-pointer bg-white hover:bg-slate-50 dark:bg-slate-950 dark:hover:bg-slate-900' }}">

                                    <input
                                        type="checkbox"
                                        name="module_ids[]"
                                        value="{{ $module->id }}"
                                        @checked($enabled)
                                        @disabled($locked)
                                        class="peer sr-only"
                                    >

                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition
                                        {{ $locked
                                            ? 'bg-slate-100 text-slate-400 dark:bg-slate-900 dark:text-slate-600'
                                            : 'bg-indigo-50 text-brand-indigo dark:bg-indigo-950/50 dark:text-indigo-300' }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            {!! $moduleIcon($module->key) !!}
                                        </svg>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-extrabold text-slate-900 dark:text-white">
                                                {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                            </span>

                                            @if ($locked)
                                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-500 dark:bg-slate-900 dark:text-slate-500">
                                                    {{ __('app.upgrade') }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="mt-1 block text-xs leading-5 text-slate-400">
                                            @if ($module->description)
                                                {{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}
                                            @else
                                                {{ __('app.module_optional_help') }}
                                            @endif
                                        </span>
                                    </span>

                                    <span class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors
                                        {{ $locked
                                            ? 'bg-slate-200 dark:bg-slate-800'
                                            : 'bg-slate-200 peer-checked:bg-brand-indigo dark:bg-slate-700 dark:peer-checked:bg-brand-indigo' }}"
                                        aria-hidden="true">
                                        <span class="absolute start-1 h-5 w-5 rounded-full bg-white shadow-[0_1px_3px_rgba(15,23,42,0.22)] transition-transform duration-150
                                            {{ $locked ? '' : 'peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5' }}"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    @error('module_ids')
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                            {{ $message }}
                        </div>
                    @enderror

                    @if (! $hasSubscription)
                        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-900 dark:text-slate-500">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 8v5m0 3.25h.01M10.3 4.7l-7 12.1A1.5 1.5 0 0 0 4.6 19h14.8a1.5 1.5 0 0 0 1.3-2.2l-7-12.1a1.95 1.95 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <p class="text-xs leading-6 text-slate-500 dark:text-slate-400">
                                {{ __('app.optional_modules_subscription_message') }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-7 flex flex-col gap-4 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-2 text-xs font-semibold leading-5 text-slate-400 dark:text-slate-500">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>{{ __('app.workspace_tip_text') }}</span>
                        </div>

                        <button type="submit"
                                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-navy px-7 py-3 text-sm font-extrabold text-white shadow-[0_6px_18px_rgba(30,42,68,0.16)] transition hover:-translate-y-px hover:bg-[#253554] hover:shadow-[0_10px_24px_rgba(30,42,68,0.20)] sm:w-auto dark:bg-brand-indigo dark:hover:bg-indigo-500">
                            {{ __('app.save_modules_continue') }}
                            <svg class="h-4 w-4 br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <div class="mt-10 hidden sm:block">
                    <div class="flex items-center justify-between gap-4 text-[11px] font-bold text-slate-400 dark:text-slate-500">
                        <span>1 {{ __('app.onboarding_steps.workspace') }}</span>
                        <span>2 {{ __('app.onboarding_steps.modules') }}</span>
                        <span>3 {{ __('app.onboarding_steps.services') }}</span>
                        <span>4 {{ __('app.onboarding_steps.hours') }}</span>
                        <span>5 {{ __('app.onboarding_steps.staff') }}</span>
                        <span>6 {{ __('app.onboarding_steps.ready') }}</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
