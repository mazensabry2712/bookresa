@extends('layouts.admin')

@section('title', __('Workspace modules').' — BookResa')
@section('heading', __('Workspace modules'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ __('Platform workspace controls') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">
                    {{ data_get($tenant->profile?->name, app()->getLocale()) ?? data_get($tenant->profile?->name, 'en') ?? $tenant->slug }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Control which modules are enabled for this workspace.') }}</p>
            </div>
            <a href="{{ route('admin.businesses.index') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('Back to businesses') }}</a>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Business') }}</p>
                <p class="mt-2 font-semibold">{{ $tenant->slug }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</p>
                <p class="mt-2 font-semibold">{{ data_get($tenant->businessType?->name, app()->getLocale()) ?? $tenant->businessType?->slug ?? '—' }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Subscription') }}</p>
                <p class="mt-2 font-semibold">{{ $subscription?->status?->value ? str($subscription->status->value)->headline() : __('No active subscription') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.businesses.modules.update', $tenant) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold">{{ __('Modules') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Core modules can be enabled or disabled per workspace. Optional modules require plan entitlement.') }}</p>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($modules as $module)
                        @php
                            $explicitEnabled = $tenantModules->has($module->id)
                                ? (bool) $tenantModules->get($module->id)
                                : $module->is_core;
                            $entitled = $module->is_core || in_array($module->key, $entitledModuleKeys, true);
                        @endphp
                        <label class="flex cursor-pointer items-center justify-between gap-4 px-5 py-4 {{ ! $entitled ? 'opacity-60' : '' }}">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">{{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}</p>
                                    @if ($module->is_core)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('Core') }}</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">{{ $entitled ? __('Included in plan') : __('Not included in plan') }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $module->key }}</p>
                            </div>
                            <input
                                type="checkbox"
                                name="module_ids[]"
                                value="{{ $module->id }}"
                                @checked($explicitEnabled && $entitled)
                                @disabled(! $module->is_active || ! $entitled)
                                class="h-4 w-4 rounded border-slate-300"
                            >
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save module settings') }}</button>
                </div>
            </section>
        </form>
    </div>
@endsection
