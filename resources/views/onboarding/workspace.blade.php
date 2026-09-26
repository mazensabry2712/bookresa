<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo
        :title="__('Configure Workspace').' — '.config('bookresa.name', 'BookResa')"
        :description="__('Configure your workspace modules and continue setup.')"
        robots="noindex,nofollow,noarchive"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:py-12">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ __('Workspace setup') }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ __('Configure your workspace') }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">
                    {{ __('Select the tools this business needs. Core modules stay enabled; additional modules depend on your plan.') }}
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">
                {{ __('Go to dashboard') }}
            </a>
        </div>

        <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @foreach ($steps as $step)
                <div class="rounded-2xl border p-4 {{ $step['complete'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $loop->iteration }}</p>
                    <p class="mt-1 font-semibold">{{ $step['label'] }}</p>
                    @if ($step['complete'])
                        <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">{{ __('Complete') }}</p>
                    @else
                        <a href="{{ route($step['route']) }}" class="mt-1 inline-block text-xs font-semibold underline underline-offset-4">{{ __('Continue') }}</a>
                    @endif
                </div>
            @endforeach
        </div>

        <section class="mt-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="border-b border-slate-200 pb-4 dark:border-slate-800">
                <p class="text-sm text-slate-500">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-xl font-semibold">{{ data_get($tenant->profile?->name, app()->getLocale()) ?? data_get($tenant->profile?->name, 'en') ?? $tenant->slug }}</h2>
            </div>

            <form method="POST" action="{{ route('onboarding.workspace.modules') }}" class="mt-5">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($modules as $module)
                        @php
                            $enabled = $tenant->modules->contains('id', $module->id);
                            $isCore = $module->is_core;
                            $entitled = $isCore || $entitledModuleKeys->contains($module->key);
                        @endphp
                        <label class="flex gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-800 {{ $isCore ? 'cursor-default' : 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-950' }}">
                            <input
                                type="checkbox"
                                name="module_ids[]"
                                value="{{ $module->id }}"
                                @checked($enabled)
                                @disabled($isCore || ! $entitled)
                                class="mt-1 rounded border-slate-300"
                            >
                            <span class="min-w-0">
                                <span class="flex items-center gap-2 font-semibold">
                                    {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                    @if ($isCore)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('Core') }}</span>
                                    @elseif (! $entitled)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-950 dark:text-amber-200">{{ __('Upgrade') }}</span>
                                    @endif
                                </span>
                                @if ($module->description)
                                    <span class="mt-1 block text-sm text-slate-500">{{ data_get($module->description, app()->getLocale()) ?? data_get($module->description, 'en') }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('module_ids')
                    <p class="mt-4 text-sm font-medium text-rose-600">{{ $message }}</p>
                @enderror

                @if (! $hasSubscription)
                    <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">{{ __('Optional modules require a subscription plan. Core modules are enabled now.') }}</p>
                @endif

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('services.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-center text-sm font-semibold dark:border-slate-700">
                        {{ __('Continue to services') }}
                    </a>
                    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                        {{ __('Save modules & continue') }}
                    </button>
                </div>
            </form>
                @if ($steps[4]['complete'] && ! $steps[5]['complete'])
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                        <p class="text-sm text-emerald-800 dark:text-emerald-200">{{ __('Your workspace configuration is complete. Finish setup to publish the booking page.') }}</p>
                        <form method="POST" action="{{ route('onboarding.complete') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Finish onboarding') }}</button>
                        </form>
                    </div>
                @endif

        </section>
    </main>
</body>
</html>
