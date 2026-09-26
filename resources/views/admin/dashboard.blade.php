@extends('layouts.admin')

@section('title', __('Admin Dashboard').' — BookResa')
@section('heading', __('Admin Dashboard'))

@section('content')
    @php
        $money = static fn (int $minor, string $currency = 'EGP'): string => number_format($minor / 100, 2).' '.$currency;
    @endphp

    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform overview') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Admin Dashboard') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Monitor workspace growth, subscriptions, bookings and platform payments.') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('Businesses'), $metrics['businesses']],
                [__('Active businesses'), $metrics['activeBusinesses']],
                [__('Suspended businesses'), $metrics['suspendedBusinesses']],
                [__('Customers'), $metrics['customers']],
            ] as [$label, $value])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('Subscriptions'), $metrics['subscriptions']],
                [__('Active / trial subscriptions'), $metrics['activeSubscriptions']],
                [__('Bookings'), $metrics['bookings']],
                [__('Trial businesses'), $metrics['trialBusinesses']],
            ] as [$label, $value])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Paid subscription revenue') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $money((int) $metrics['paidSubscriptionRevenueMinor']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Usage revenue') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $money((int) $metrics['usageRevenueMinor']) }}</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <h3 class="font-semibold">{{ __('Recent businesses') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Latest workspaces created on the platform.') }}</p>
                </div>
                <a href="{{ route('admin.businesses.index') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('View all') }}</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('Business') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Type') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Team') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($recentBusinesses as $business)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ data_get($business->profile?->name, app()->getLocale()) ?? $business->slug }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $business->slug }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ data_get($business->businessType?->name, app()->getLocale()) ?? $business->businessType?->slug ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $business->status === AppDomainTenantEnumsTenantStatus::Active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200' }}">
                                        {{ str($business->status->value)->headline() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ $business->memberships_count }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $business->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No businesses yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
