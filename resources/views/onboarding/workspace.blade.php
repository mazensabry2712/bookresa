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

<body class="min-h-screen bg-[#f5f7fb] text-slate-950 antialiased dark:bg-[#080d19] dark:text-white">
    @php
        $moduleStepIndex = collect($steps)->search(static fn (array $step): bool => $step['key'] === 'modules');
        $current = $currentStepIndex === false ? $moduleStepIndex : $currentStepIndex;
        $enabledModuleCount = $tenant->modules->filter(
            static fn ($tenantModule): bool => (bool) data_get($tenantModule->pivot, 'enabled', false)
        )->count();
        $businessName = data_get($tenant->profile?->name, app()->getLocale())
            ?? data_get($tenant->profile?->name, 'en')
            ?? $tenant->slug;
    @endphp

    <div class="min-h-screen lg:flex">
        <aside class="hidden w-[330px] shrink-0 flex-col bg-[#17233c] px-8 py-8 text-white lg:flex xl:w-[370px]">
            <div class="flex items-center">
                <img src="{{ asset('logodark.png') }}"
                     alt="BookResa"
                     width="707"
                     height="353"
                     decoding="async"
                     class="h-10 w-auto object-contain">
            </div>

            <div class="mt-12">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-200/70">
                    {{ __('app.step_progress', ['current' => $current + 1, 'total' => count($steps)]) }}
                </p>

                <h1 class="mt-4 max-w-xs text-3xl font-black leading-tight tracking-tight">
                    {{ __('app.configure_workspace') }}
                </h1>

                <p class="mt-4 max-w-xs text-sm leading-7 text-indigo-100/70">
                    {{ __('app.configure_workspace_message') }}
                </p>
            </div>

            <div class="mt-12">
                <div class="space-y-1">
                    @foreach ($steps as $step)
                        @php
                            $isCurrent = $step['key'] === 'modules';
                            $isComplete = $step['complete'];
                        @endphp

                        <div class="relative flex items-center gap-3 rounded-2xl px-3 py-3
                            {{ $isCurrent ? 'bg-white/10' : '' }}">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-xs font-black
                                {{ $isComplete
                                    ? 'border-emerald-400 bg-emerald-400 text-[#17233c]'
                                    : ($isCurrent
                                        ? 'border-white bg-white text-[#17233c]'
                                        : 'border-white/20 text-white/40') }}">
                                @if ($isComplete)
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>

                            <span class="min-w-0">
                                <span class="block text-sm font-extrabold {{ $isCurrent ? 'text-white' : 'text-white/65' }}">
                                    {{ $step['label'] }}
                                </span>
                                <span class="mt-0.5 block text-xs {{ $isComplete ? 'text-emerald-300' : 'text-white/35' }}">
                                    {{ $isComplete ? __('app.complete') : ($isCurrent ? __('app.current_step') : __('app.locked')) }}
                                </span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-auto pt-10">
                <div class="border-t border-white/10 pt-5">
                    <p class="text-xs font-semibold text-white/40">{{ __('app.workspace') }}</p>
                    <p class="mt-1 truncate text-sm font-extrabold text-white/85">{{ $businessName }}</p>
                </div>
            </div>
        </aside>

        <main class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between gap-4 px-5 py-5 sm:px-8 lg:px-10">
                <div class="lg:hidden">
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
                </div>

                <div class="hidden lg:block">
                    <span class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ $businessName }}</span>
                </div>

                <div class="ms-auto flex items-center gap-2">
                    <x-locale-switcher compact />
                    <x-theme-toggle compact />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="hidden min-h-10 items-center rounded-xl px-3 text-xs font-bold text-slate-500 transition hover:bg-white hover:text-slate-800 sm:inline-flex dark:hover:bg-slate-900 dark:hover:text-white">
                            {{ __('app.logout') }}
                        </button>
                    </form>
                </div>
            </header>

            <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col px-5 pb-8 sm:px-8 lg:px-12 lg:pb-12">
                <div class="mb-7 lg:hidden">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-extrabold text-brand-indigo">
                            {{ __('app.step_progress', ['current' => $current + 1, 'total' => count($steps)]) }}
                        </span>
                        <span class="text-xs font-semibold text-slate-400">
                            {{ $enabledModuleCount }} {{ __('app.modules_enabled') }}
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-6 gap-1">
                        @foreach ($steps as $step)
                            <span class="h-1.5 rounded-full {{ $step['complete'] ? 'bg-emerald-500' : ($loop->index === $moduleStepIndex ? 'bg-brand-indigo' : 'bg-slate-200 dark:bg-slate-800') }}"></span>
                        @endforeach
                    </div>
                </div>

                <section class="my-auto py-4 lg:py-12">
                    <div class="max-w-2xl">
                        <div class="hidden items-center gap-2 lg:flex">
                            <span class="text-xs font-extrabold uppercase tracking-[0.16em] text-brand-indigo">
                                {{ __('app.workspace_modules') }}
                            </span>
                            <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span class="text-xs font-bold text-slate-400">
                                {{ $enabledModuleCount }} {{ __('app.modules_enabled') }}
                            </span>
                        </div>

                        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                            {{ __('app.choose_modules') }}
                        </h2>

                        <p class="mt-3 max-w-xl text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-base">
                            {{ __('app.choose_modules_message') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="mt-9">
                        @csrf

                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
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

                                <label class="group flex cursor-pointer items-center gap-4 px-5 py-4 transition
                                    {{ $index < count($modules) - 1 ? 'border-b border-slate-100 dark:border-slate-800' : '' }}
                                    {{ $locked ? 'bg-slate-50/70 dark:bg-slate-950/40' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                                    <input
                                        type="checkbox"
                                        name="module_ids[]"
                                        value="{{ $module->id }}"
                                        @checked($enabled)
                                        @disabled($isCore || $locked)
                                        class="peer sr-only"
                                        data-module-checkbox
                                    >

                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                        {{ $locked
                                            ? 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
                                            : 'bg-indigo-50 text-brand-indigo dark:bg-indigo-950/50 dark:text-indigo-300' }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            @if ($module->key === 'calendar')
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
                                            @elseif ($module->key === 'appointments')
                                                <rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M8 3v4M16 3v4M4 9h16M8 13h3M8 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
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
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                    {{ __('app.core') }}
                                                </span>
                                            @elseif ($locked)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                    {{ __('app.upgrade') }}
                                                </span>
                                            @endif

                                            @if ($locked)
                                                <span class="hidden text-xs font-semibold text-slate-400 sm:inline">
                                                    {{ __('app.no_subscription_yet') }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">
                                            @if ($module->description)
                                                {{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}
                                            @else
                                                {{ $isCore ? __('app.module_core_help') : __('app.module_optional_help') }}
                                            @endif
                                        </span>
                                    </span>

                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border-2
                                        {{ $locked ? 'border-slate-200 text-transparent dark:border-slate-700' : 'border-slate-300 bg-white text-transparent peer-checked:border-brand-indigo peer-checked:bg-brand-indigo peer-checked:text-white dark:border-slate-600 dark:bg-slate-900' }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 12 4 4 8-8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
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
                            <p class="mt-4 text-xs leading-5 text-slate-400 dark:text-slate-500">
                                {{ __('app.optional_modules_subscription_message') }}
                            </p>
                        @endif

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">
                                {{ __('app.core_modules_stay_enabled') }}
                            </span>

                            <button type="submit"
                                    class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#253554] hover:shadow-md sm:w-auto dark:bg-brand-indigo dark:hover:bg-indigo-500">
                                {{ __('app.save_modules_continue') }}
                                <svg class="h-4 w-4 br-direction-arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>

                <div class="mt-7 flex items-center justify-between gap-4 text-xs">
                    <span class="font-semibold text-slate-400 dark:text-slate-500">
                        {{ __('app.workspace_tip_text') }}
                    </span>
                    <span class="hidden font-extrabold text-slate-400 sm:inline dark:text-slate-500">
                        BookResa
                    </span>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.querySelectorAll('[data-module-checkbox]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                checkbox.closest('label')?.classList.toggle('bg-indigo-50/70', checkbox.checked);
                checkbox.closest('label')?.classList.toggle('dark:bg-indigo-950/20', checkbox.checked);
            });
        });
    </script>
</body>
</html>
