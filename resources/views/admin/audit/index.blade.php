@extends('layouts.admin')

@section('title', __('platform.activity_log').' — BookResa')
@section('heading', __('platform.activity_log'))

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm text-slate-500">{{ __('platform.activity_log_help') }}</p>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('platform.activity_log') }}</h2></div><a href="{{ route('admin.audit.export', request()->query()) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Export CSV') }}</a></div>
        </section>

        <form method="GET" class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:grid-cols-2 xl:grid-cols-5">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('platform.activity_search') }}"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <select name="tenant_id" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('platform.all_workspaces') }}</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((int) request('tenant_id') === (int) $tenant->id)>
                        {{ data_get($tenant->profile?->name, app()->getLocale()) ?? $tenant->slug }}
                    </option>
                @endforeach
            </select>
            <select name="causer_id" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('platform.all_platform_admins') }}</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}" @selected((int) request('causer_id') === (int) $actor->id)>
                        {{ $actor->name }} — {{ $actor->email }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <input type="date" name="to" value="{{ request('to') }}"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <div class="flex gap-2 sm:col-span-2 xl:col-span-5">
                <button type="submit" class="rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white">{{ __('platform.filter') }}</button>
                <a href="{{ route('admin.audit.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">{{ __('platform.clear_filters') }}</a>
            </div>
        </form>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('platform.activity_action') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.activity_actor') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.workspace') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.activity_context') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($activities as $activity)
                            @php
                                $properties = $activity->properties?->toArray() ?? [];
                                $workspaceId = data_get($properties, 'tenant_id');
                                $workspace = $workspaceId !== null ? $tenants->firstWhere('id', (int) $workspaceId) : null;
                                $actor = $activity->causer;
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950 dark:text-white">{{ $activity->description }}</p>
                                    @if ($activity->subject_type && $activity->subject_id)
                                        <p class="mt-1 text-xs text-slate-500">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ $actor?->name ?? __('platform.system') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $actor?->email ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-500">
                                    {{ data_get($workspace?->profile?->name, app()->getLocale()) ?? $workspace?->slug ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-500">
                                    @foreach (array_filter([
                                        __('platform.ip') => data_get($properties, 'ip'),
                                        __('platform.route') => data_get($properties, 'route'),
                                        __('platform.status') => data_get($properties, 'status'),
                                        __('platform.role') => data_get($properties, 'role'),
                                    ]) as $label => $value)
                                        <div><span class="font-semibold">{{ $label }}:</span> {{ $value }}</div>
                                    @endforeach
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">
                                    {{ $activity->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">{{ __('platform.no_activity') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($activities->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $activities->links() }}</div>
            @endif
        </section>
    </div>
@endsection
