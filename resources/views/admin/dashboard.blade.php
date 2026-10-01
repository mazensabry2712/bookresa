@extends('layouts.admin')

@section('title', __('platform.platform_command_center').' — BookResa')
@section('heading', __('platform.platform_command_center'))

@section('content')
    @php
        $money = static fn (int $minor, string $currency): string => number_format($minor / 100, 2).' '.$currency;
        $workspaceStatus = static fn ($status): string => str((string) ($status?->value ?? $status))->headline();
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ __('platform.platform_command_center_help') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('platform.platform_command_center') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.businesses.index') }}" class="rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white">{{ __('platform.workspaces') }}</a>
                <a href="{{ route('admin.customers.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">{{ __('platform.customer_intelligence') }}</a>
                <a href="{{ route('admin.audit.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">{{ __('platform.activity_log') }}</a>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('platform.total_workspaces'), $metrics['businesses']],
                [__('platform.active_workspaces'), $metrics['activeBusinesses']],
                [__('platform.suspended_workspaces'), $metrics['suspendedBusinesses']],
                [__('platform.total_customers'), $metrics['customers']],
                [__('platform.total_bookings'), $metrics['bookings']],
                [__('platform.total_users'), $metrics['users']],
                [__('platform.new_workspaces_30d'), $metrics['newWorkspaces30d']],
                [__('platform.new_customers_30d'), $metrics['newCustomers30d']],
            ] as [$label, $value])
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('platform.subscriptions'), $metrics['subscriptions']],
                [__('platform.active_subscriptions'), $metrics['activeSubscriptions']],
                [__('platform.trial_workspaces'), $metrics['trialBusinesses']],
                [__('platform.expired_workspaces'), $metrics['expiredBusinesses']],
                [__('platform.bookings_30d'), $metrics['bookings30d']],
                [__('platform.failed_payments'), $metrics['failedPayments']],
                [__('platform.over_limit_workspaces'), $metrics['overLimitBusinesses']],
                [__('platform.connected_payment_accounts'), $metrics['connectedPaymentAccounts']],
            ] as [$label, $value])
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div>
                    <h3 class="font-semibold">{{ __('platform.mrr_by_currency') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('platform.currency_totals_help') }}</p>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @forelse ($metrics['mrrByCurrency'] as $currency => $minor)
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $currency }}</p>
                            <p class="mt-1 text-xl font-bold">{{ $money((int) $minor, $currency) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('platform.no_revenue') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div>
                    <h3 class="font-semibold">{{ __('platform.usage_revenue_by_currency') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('platform.currency_totals_help') }}</p>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @forelse ($metrics['additionalUsageRevenueByCurrency'] as $currency => $minor)
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $currency }}</p>
                            <p class="mt-1 text-xl font-bold">{{ $money((int) $minor, $currency) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('platform.no_revenue') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ([
                [$subscriptionRevenueByCurrency, __('platform.subscription_revenue_by_currency')],
                [$bookingRevenueByCurrency, __('platform.booking_revenue_by_currency')],
            ] as [$rows, $title])
                <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <h3 class="font-semibold">{{ $title }}</h3>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($rows as $row)
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div>
                                    <p class="font-semibold">{{ strtoupper($row->currency) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ number_format($row->payment_count) }} {{ __('platform.payments') }}</p>
                                </div>
                                <p class="font-bold">{{ $money((int) $row->total_minor, strtoupper($row->currency)) }}</p>
                            </div>
                        @empty
                            <p class="px-5 py-10 text-center text-sm text-slate-500">{{ __('platform.no_revenue') }}</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <h3 class="font-semibold">{{ __('platform.workspace_360') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('platform.latest_workspaces_help') }}</p>
                </div>
                <a href="{{ route('admin.businesses.index') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('platform.view_all') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('platform.workspace') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.customers') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.bookings') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.team') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('platform.created') }}</th>
                            <th class="px-5 py-3 text-end">{{ __('platform.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($recentBusinesses as $business)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ data_get($business->profile?->name, app()->getLocale()) ?? $business->slug }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $business->slug }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $business->status === \App\Domain\Tenant\Enums\TenantStatus::Active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $workspaceStatus($business->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">{{ number_format($business->customers_count) }}</td>
                                <td class="px-5 py-4">{{ number_format($business->bookings_count) }}</td>
                                <td class="px-5 py-4">{{ number_format($business->memberships_count) }}</td>
                                <td class="px-5 py-4 text-xs text-slate-500">{{ $business->created_at?->format('Y-m-d') }}</td>
                                <td class="px-5 py-4 text-end">
                                    <a href="{{ route('admin.businesses.show', $business) }}" class="font-semibold underline underline-offset-4">{{ __('platform.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">{{ __('platform.no_workspaces') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <h3 class="font-semibold">{{ __('platform.recent_security_activity') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('platform.recent_security_activity_help') }}</p>
                </div>
                <a href="{{ route('admin.audit.index') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('platform.activity_log') }}</a>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($recentActivity as $activity)
                    <div class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold">{{ $activity->description }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $activity->causer?->name ?? __('platform.system') }}</p>
                        </div>
                        <time class="text-xs text-slate-500">{{ $activity->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</time>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">{{ __('platform.no_activity') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
