@extends('layouts.dashboard')

@section('title', __('app.report_ui.reports').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.report_ui.reports'))

@section('content')
    @php
        $money = static fn (int $minor): string => number_format($minor / 100, 2).' '.$currency;
        $totalBookings = max((int) $metrics['bookings'], 1);
        $statusColors = [
            'pending' => 'bg-amber-400',
            'confirmed' => 'bg-indigo-500',
            'rescheduled' => 'bg-indigo-400',
            'completed' => 'bg-emerald-500',
            'cancelled' => 'bg-slate-400',
            'no_show' => 'bg-rose-500',
        ];
        $statusLabels = [
            'pending' => __('app.report_ui.status_pending'),
            'confirmed' => __('app.report_ui.status_confirmed'),
            'rescheduled' => __('app.report_ui.status_rescheduled'),
            'completed' => __('app.report_ui.status_completed'),
            'cancelled' => __('app.report_ui.status_cancelled'),
            'no_show' => __('app.report_ui.status_no_show'),
        ];
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.report_ui.title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.report_ui.page_help') }}</p>
            </div>

            <form method="GET" class="flex flex-wrap items-end gap-2">
                <label>
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.report_ui.from') }}</span>
                    <input type="date" name="from" value="{{ $fromDate }}" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.report_ui.to') }}</span>
                    <input type="date" name="to" value="{{ $toDate }}" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </label>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">{{ __('app.report_ui.apply') }}</button>
                <a href="{{ route('reports.business') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.report_ui.reset') }}</a>
            </form>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                [__('app.report_ui.bookings'), number_format($metrics['bookings'])],
                [__('app.report_ui.customers'), number_format($metrics['customers'])],
                [__('app.report_ui.revenue'), $money($metrics['revenueMinor'])],
                [__('app.report_ui.completed'), number_format($metrics['completed'])],
                [__('app.report_ui.cancellations'), number_format($metrics['cancellations'])],
                [__('app.report_ui.no_shows'), number_format($metrics['noShows'])],
            ] as [$label, $value])
                <article class="br-panel p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-xl font-extrabold text-slate-950 dark:text-white">{{ $value }}</p>
                </article>
            @endforeach
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <section class="br-panel overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.report_ui.bookings_by_status') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $fromDate }} → {{ $toDate }} · {{ $timezone }}</p>
                        </div>
                        <span class="rounded-full border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-500 dark:border-slate-700">{{ number_format($metrics['bookings']) }}</span>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    @foreach ($statuses as $status)
                        @php
                            $count = (int) $statusCounts->get($status->value, 0);
                            $percent = (int) round(($count / $totalBookings) * 100);
                        @endphp

                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $statusLabels[$status->value] ?? str($status->value)->headline() }}</span>
                                <span class="font-bold text-slate-950 dark:text-white">{{ number_format($count) }} · {{ $percent }}%</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $statusColors[$status->value] ?? 'bg-slate-400' }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="br-panel overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.report_ui.top_services') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.report_ui.top_services_help') }}</p>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($topServices as $row)
                        @php
                            $serviceCount = (int) $row->booking_count;
                            $serviceMax = max((int) $topServices->max('booking_count'), 1);
                            $servicePercent = (int) round(($serviceCount / $serviceMax) * 100);
                        @endphp
                        <div class="px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="min-w-0 truncate text-sm font-bold text-slate-950 dark:text-white">{{ data_get($row->service?->name, app()->getLocale()) ?? data_get($row->service?->name, 'en') ?? '—' }}</span>
                                <span class="shrink-0 text-xs font-bold text-slate-500">{{ number_format($serviceCount) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-brand-indigo" style="width: {{ $servicePercent }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <p class="font-bold text-slate-950 dark:text-white">{{ __('app.report_ui.no_data') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.report_ui.no_data_help') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="br-panel p-5 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.report_ui.reading') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.report_ui.report_scope') }}</h3>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ __('app.report_ui.scope_help') }}</p>
                </div>
                <span class="text-sm font-bold text-slate-500">{{ $fromDate }} → {{ $toDate }}</span>
            </div>
        </section>
    </div>
@endsection
