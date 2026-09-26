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
    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('onboarding.workspace') }}" class="text-lg font-extrabold tracking-tight text-slate-950 dark:text-white">BookResa</a>
                <span class="h-5 w-px bg-slate-200 dark:bg-slate-800" aria-hidden="true"></span>
                <span class="truncate text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $tenant->slug }}</span>
            </div>
            <div class="flex items-center gap-2">
                <x-locale-switcher />
                <x-theme-toggle />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                        {{ __('app.logout') }}
                    </button>
                </form>
            </div>
        </header>

        <section class="mt-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-brand-indigo">{{ __('app.step_progress', ['current' => 2, 'total' => 6]) }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ __('app.configure_workspace') }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.configure_workspace_message') }}</p>
            </div>

            <div class="mt-6 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full w-2/6 rounded-full bg-brand-indigo"></div>
            </div>
        </section>

        <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($steps as $step)
                <div class="br-panel p-4 {{ $step['complete'] ? 'ring-1 ring-emerald-200/80 dark:ring-emerald-900/70' : '' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        @if ($step['complete'])
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">✓</span>
                        @endif
                    </div>
                    <p class="mt-3 text-sm font-bold text-slate-900 dark:text-white">{{ $step['label'] }}</p>
                    @if (! $step['complete'] && $loop->index > 0)
                        <a href="{{ route($step['route']) }}" class="mt-2 inline-block text-xs font-bold text-brand-indigo hover:underline">{{ __('app.continue') }}</a>
                    @elseif ($step['complete'])
                        <p class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ __('app.complete') }}</p>
                    @else
                        <p class="mt-2 text-xs font-semibold text-slate-400">{{ __('app.current_step') }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <section class="br-panel mt-8 overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                <p class="text-sm text-slate-500">{{ data_get($tenant->profile?->name, app()->getLocale()) ?? data_get($tenant->profile?->name, 'en') ?? $tenant->slug }}</p>
                <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ __('app.choose_modules') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    {{ __('app.choose_modules_message') }}
                </p>
            </div>

            <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="p-5 sm:p-6">
                @csrf

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($modules as $module)
                        @php
                            $enabled = $tenant->modules->contains('id', $module->id);
                            $isCore = $module->is_core;
                            $entitled = $isCore || $entitledModuleKeys->contains($module->key);
                        @endphp

                        <label class="group flex gap-3 rounded-2xl border border-slate-200 p-4 transition dark:border-slate-800 {{ $isCore || ! $entitled ? 'bg-slate-50/70 dark:bg-slate-900/60' : 'cursor-pointer hover:border-brand-indigo/40 hover:bg-slate-50 dark:hover:bg-slate-900' }}">
                            <input
                                type="checkbox"
                                name="module_ids[]"
                                value="{{ $module->id }}"
                                @checked($enabled)
                                @disabled($isCore || ! $entitled)
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-indigo focus:ring-brand-indigo dark:border-slate-600 dark:bg-slate-900"
                            >
                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                                    {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                    @if ($isCore)
                                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('app.core') }}</span>
                                    @elseif (! $entitled)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800 dark:bg-amber-950 dark:text-amber-200">{{ __('app.upgrade') }}</span>
                                    @endif
                                </span>
                                @if ($module->description)
                                    <span class="mt-1.5 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('module_ids')
                    <p class="mt-4 text-sm font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror

                @if (! $hasSubscription)
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        {{ __('app.optional_modules_subscription_message') }}
                    </div>
                @endif

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('services.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.skip_to_services') }}
                    </a>
                    <button type="submit" class="rounded-xl bg-brand-navy px-5 py-3 text-sm font-bold text-white shadow-sm hover:-translate-y-px hover:shadow-md dark:bg-brand-indigo">
                        {{ __('app.save_modules_continue') }}
                    </button>
                </div>
            </form>

            @if ($steps[4]['complete'] && ! $steps[5]['complete'])
                <div class="border-t border-slate-200 bg-emerald-50/70 px-5 py-5 dark:border-slate-800 dark:bg-emerald-950/20 sm:px-6">
                    <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ __('app.workspace_configuration_complete') }}</p>
                    <form method="POST" action="{{ route('onboarding.complete') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white dark:bg-brand-indigo">{{ __('app.finish_onboarding') }}</button>
                    </form>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
