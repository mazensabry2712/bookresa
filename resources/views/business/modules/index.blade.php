@extends('layouts.dashboard')

@section('title', __('app.module_ui.modules').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.module_ui.modules'))

@section('content')
    @php
        $enabledKeys = $tenant->modules
            ->filter(fn ($module) => (bool) ($module->pivot->enabled ?? false))
            ->pluck('key')
            ->all();

        $coreModules = $modules->where('is_core', true);
        $optionalModules = $modules->where('is_core', false);
        $availableOptionalModules = $optionalModules->filter(fn ($module) => $entitledModuleKeys->contains($module->key));
        $lockedOptionalModules = $optionalModules->reject(fn ($module) => $entitledModuleKeys->contains($module->key));
        $businessName = data_get($tenant->profile?->name, app()->getLocale())
            ?? data_get($tenant->profile?->name, 'en')
            ?? $tenant->slug;
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-brand-indigo">{{ __('app.settings') }}</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ __('app.module_ui.modules') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.module_ui.page_help') }}</p>
            </div>

            <div class="shrink-0 rounded-2xl br-surface-soft px-4 py-3">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.business') }}</p>
                <p class="mt-1 max-w-[18rem] truncate text-sm font-bold text-slate-950 dark:text-white">{{ $businessName }}</p>
            </div>
        </section>

        <form method="POST" action="{{ route('business.modules.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="br-panel overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.module_ui.core') }}</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.module_ui.core') }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.module_ui.core_help') }}</p>
                        </div>
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                            {{ __('app.included') }}
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($coreModules as $module)
                        <div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="3"/><path stroke-linecap="round" d="M8 12h8M12 8v8"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $module->key }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs font-bold text-emerald-600 dark:text-emerald-400">✓</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="br-panel overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.module_ui.add_ons') }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.module_ui.add_ons') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.module_ui.add_ons_help') }}</p>
                    </div>
                </div>

                @if ($availableOptionalModules->isNotEmpty())
                    <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
                        @foreach ($availableOptionalModules as $module)
                            @php
                                $enabled = in_array($module->key, $enabledKeys, true);
                            @endphp
                            <label class="group flex cursor-pointer items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-px hover:border-brand-indigo/40 hover:shadow-sm dark:border-slate-800 dark:bg-slate-950 dark:hover:border-indigo-500/40">
                                <input type="checkbox"
                                       name="module_ids[]"
                                       value="{{ $module->id }}"
                                       @checked($enabled)
                                       class="peer sr-only">
                                <span class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition peer-checked:border-brand-indigo peer-checked:bg-brand-indigo peer-checked:text-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:peer-checked:border-indigo-500 dark:peer-checked:bg-indigo-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5v14"/></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}</span>
                                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">{{ __('app.module_ui.included_in_plan') }}</span>
                                    </span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">{{ data_get(__('app.module_descriptions'), $module->key, __('app.module_ui.add_ons_help')) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @elseif ($lockedOptionalModules->isNotEmpty())
                    <div class="p-5 sm:p-6">
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-950/50">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-950 dark:text-white">{{ __('app.module_ui.no_subscription') }}</p>
                                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.module_ui.unlock_message') }}</p>
                                </div>
                                <a href="{{ route('billing.subscription') }}"
                                   class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:-translate-y-px hover:shadow-md dark:bg-brand-indigo">
                                    {{ __('app.explore_plans') }}
                                </a>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($lockedOptionalModules as $module)
                                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                                        {{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if ($availableOptionalModules->isNotEmpty())
                    <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="text-xs leading-5 text-slate-500">{{ __('app.module_ui.core_help') }}</p>
                        <button type="submit"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            {{ __('app.module_ui.save') }}
                        </button>
                    </div>
                @endif
            </section>
        </form>
    </div>
@endsection
