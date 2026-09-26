@extends('layouts.admin')

@section('title', __('Platform reports').' — BookResa')
@section('heading', __('Platform reports'))

@section('content')
    @php
        $money = static fn (int $minor): string => number_format($minor / 100, 2).' EGP';
    @endphp

    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Reports') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Platform reports') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Platform-level businesses, bookings, subscriptions, usage and revenue.') }}</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-[1fr_1fr_auto_auto] dark:bg-slate-900 dark:ring-slate-800">
            <label class="text-sm">
                <span class="mb-1 block font-medium">{{ __('From') }}</span>
                <input type="date" name="from" value="{{ $fromDate }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
            </label>
            <label class="text-sm">
                <span class="mb-1 block font-medium">{{ __('To') }}</span>
                <input type="date" name="to" value="{{ $toDate }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
            </label>
            <button class="self-end rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Apply') }}</button>
            <a href="{{ route('admin.reports.index') }}" class="self-end rounded-xl border border-slate-300 px-5 py-2.5 text-center text-sm font-semibold dark:border-slate-700">{{ __('Reset') }}</a>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                [__('Businesses'), $metrics['businesses']],
                [__('New businesses'), $metrics['newBusinesses']],
                [__('Active businesses'), $metrics['activeBusinesses']],
                [__('Suspended businesses'), $metrics['suspendedBusinesses']],
                [__('Bookings'), $metrics['bookings']],
                [__('Subscriptions started'), $metrics['subscriptionsStarted']],
                [__('Active subscriptions'), $metrics['activeSubscriptions']],
                [__('Over-limit businesses'), $metrics['overLimitBusinesses']],
                [__('Subscription revenue'), $money($metrics['subscriptionRevenueMinor'])],
                [__('Usage revenue'), $money($metrics['usageRevenueMinor'])],
                [__('Total platform revenue'), $money($metrics['platformRevenueMinor'])],
            ] as [$label, $value])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl bg-slate-100 px-5 py-4 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300">
            {{ __('All platform statistics are tenant-independent.') }}
            <span class="ms-2 text-slate-400">{{ $fromDate }} → {{ $toDate }} · {{ $timezone }}</span>
        </div>
    </div>
@endsection
