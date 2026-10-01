@extends('layouts.admin')

@section('title', __('platform.platform_security').' — BookResa')
@section('heading', __('platform.platform_security'))

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm text-slate-500">{{ __('platform.platform_security_help') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('platform.platform_security') }}</h2>
        </section>

        <div class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('platform.two_factor_status') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $twoFactorConfigured ? __('platform.enabled') : __('platform.not_configured') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ __('platform.two_factor_status_help') }}</p>
            </article>
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('platform.platform_admins') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ number_format($platformAdmins->count()) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ __('platform.platform_admins_help') }}</p>
            </article>
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('platform.security_events') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ number_format($recentSecurityEvents->count()) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ __('platform.security_events_help') }}</p>
            </article>
        </div>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold">{{ __('platform.platform_admins') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('platform.platform_admins_help') }}</p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('platform.user') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('platform.status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('platform.two_factor') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($platformAdmins as $platformAdmin)
                            @php
                                $adminUser = $platformAdmin->user;
                                $configured = filled($adminUser?->two_factor_secret) && filled($adminUser?->two_factor_confirmed_at);
                            @endphp
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-semibold">{{ $adminUser?->name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $adminUser?->email ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-4">{{ $platformAdmin->is_active ? __('platform.active') : __('platform.inactive') }}</td>
                                <td class="px-4 py-4">{{ $configured ? __('platform.enabled') : __('platform.not_configured') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h3 class="font-semibold">{{ __('platform.recent_security_activity') }}</h3>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($recentSecurityEvents as $event)
                    <div class="px-5 py-4">
                        <p class="font-semibold">{{ $event->description }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $event->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">{{ __('platform.no_activity') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
