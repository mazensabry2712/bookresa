@extends('layouts.dashboard')

@section('title', __('Business reports').' — '.config('app.name', 'BookResa'))
@section('heading', __('Business reports'))

@section('content')
    @php
        $money = static fn (int $minor): string => number_format($minor / 100, 2).' '.($tenant->profile?->booking_settings['currency'] ?? 'EGP');
    @endphp

    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Reports') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Business reports') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Bookings, customers and revenue for the selected period.') }}</p>
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
            <a href="{{ route('reports.business') }}" class="self-end rounded-xl border border-slate-300 px-5 py-2.5 text-center text-sm font-semibold dark:border-slate-700">{{ __('Reset') }}</a>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                [__('Bookings'), $metrics['bookings']],
                [__('Customers'), $metrics['customers']],
                [__('Revenue'), $money($metrics['revenueMinor'])],
                [__('Completed'), $metrics['completed']],
                [__('Cancellations'), $metrics['cancellations']],
                [__('No-shows'), $metrics['noShows']],
            ] as [$label, $value])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold">{{ __('Bookings by status') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $fromDate }} → {{ $toDate }} · {{ $timezone }}</p>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($statuses as $status)
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm">{{ __(str($status->value)->headline()) }}</span>
                            <span class="font-semibold">{{ number_format((int) $statusCounts->get($status->value, 0)) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold">{{ __('Top services') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Most booked services in this period.') }}</p>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($topServices as $row)
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm font-medium">{{ data_get($row->service?->name, app()->getLocale()) ?? data_get($row->service?->name, 'en') ?? '—' }}</span>
                            <span class="text-sm text-slate-500">{{ number_format($row->booking_count) }} {{ __('bookings') }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('No report data for this period.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
