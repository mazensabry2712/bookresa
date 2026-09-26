@extends('layouts.dashboard')

@section('title', __('Calendar').' — '.config('bookresa.name', 'Velto'))
@section('heading', __('Calendar'))

@section('content')
    @php
        $statusLabel = fn ($value) => str($value)->replace('_', ' ')->title();
        $serviceName = fn ($service) => data_get($service?->name, app()->getLocale())
            ?? data_get($service?->name, 'en')
            ?? '—';
        $dayLabels = [
            1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'),
            5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun'),
        ];
        $viewLabel = match ($viewMode) {
            'day' => __('Day'),
            'week' => __('Week'),
            default => __('Month'),
        };
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ $tenant->slug }} · {{ $timezone }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ $viewLabel }} · {{ $reference->format('F Y') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Manage appointments by day, week or month.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month')] as $key => $label)
                    <a href="{{ route('calendar.index', ['view' => $key, 'date' => $reference->format('Y-m-d'), 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                       class="rounded-xl border px-3 py-2 text-sm font-semibold {{ $viewMode === $key ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $previousDate->format('Y-m-d'), 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                   class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-slate-700">{{ __('Previous') }}</a>
                <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $today]) }}"
                   class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Today') }}</a>
                <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $nextDate->format('Y-m-d'), 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                   class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-slate-700">{{ __('Next') }}</a>
            </div>
            <p class="text-sm font-medium text-slate-500">
                {{ $periodStart->format('d M Y') }} — {{ $periodEnd->format('d M Y') }}
            </p>
        </div>

        <form method="GET" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <input type="hidden" name="date" value="{{ $reference->format('Y-m-d') }}">
            <div class="grid gap-3 sm:grid-cols-3">
                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Service') }}</span>
                    <select name="service_id" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All services') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) request('service_id') === (string) $service->id)>{{ $serviceName($service) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Staff') }}</span>
                    <select name="staff_id" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All staff') }}</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>{{ $member->display_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</span>
                    <select name="status" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $statusLabel($status->value) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <button type="submit" class="mt-4 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Apply filters') }}</button>
        </form>

        @if ($viewMode === 'month')
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="grid grid-cols-7 divide-x divide-slate-200 border-b border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    @foreach ($dayLabels as $label)
                        <div class="bg-slate-50 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-950/50">{{ $label }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7 divide-x divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach ($calendarDays as $day)
                        @php $dayBookings = $bookingsByDate->get($day->toDateString(), collect()); @endphp
                        <div class="min-h-36 p-2 {{ $day->format('Y-m-d') === $today ? 'bg-slate-50 dark:bg-slate-950/30' : 'bg-white dark:bg-slate-900' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold">{{ $day->day }}</span>
                                @if ($dayBookings->isNotEmpty()) <span class="text-[11px] text-slate-400">{{ $dayBookings->count() }}</span> @endif
                            </div>
                            <div class="mt-2 space-y-1.5">
                                @foreach ($dayBookings->take(5) as $booking)
                                    <a href="{{ route('booking.management.show', $booking) }}" class="block rounded-lg border border-slate-200 px-2 py-1.5 text-xs hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                                        <div class="font-bold">{{ $booking->starts_at->setTimezone($timezone)->format('H:i') }}</div>
                                        <div class="truncate font-semibold">{{ $booking->customer?->name ?? '—' }}</div>
                                        <div class="truncate text-slate-500">{{ $serviceName($booking->service) }}</div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif ($viewMode === 'week')
            <div class="grid gap-3 lg:grid-cols-7">
                @foreach ($calendarDays as $day)
                    @php $dayBookings = $bookingsByDate->get($day->toDateString(), collect()); @endphp
                    <section class="min-h-72 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $dayLabels[$day->dayOfWeekIso] }}</p>
                                <p class="mt-1 font-bold">{{ $day->format('d M') }}</p>
                            </div>
                            <span class="text-xs text-slate-400">{{ $dayBookings->count() }}</span>
                        </div>
                        <div class="mt-3 space-y-2">
                            @forelse ($dayBookings as $booking)
                                <a href="{{ route('booking.management.show', $booking) }}" class="block rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                    <p class="text-xs font-bold">{{ $booking->starts_at->setTimezone($timezone)->format('H:i') }} — {{ $booking->ends_at->setTimezone($timezone)->format('H:i') }}</p>
                                    <p class="mt-1 truncate text-sm font-semibold">{{ $booking->customer?->name ?? '—' }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500">{{ $serviceName($booking->service) }} · {{ $booking->staff?->display_name ?? __('Auto assigned') }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $statusLabel($booking->status->value) }} · {{ $statusLabel($booking->payment_status->value) }}</p>
                                </a>
                            @empty
                                <p class="py-8 text-center text-xs text-slate-400">{{ __('No bookings') }}</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        @else
            @php $dayBookings = $bookingsByDate->get($reference->toDateString(), collect()); @endphp
            <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold">{{ $reference->format('l, d F Y') }}</h3>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($dayBookings as $booking)
                        <a href="{{ route('booking.management.show', $booking) }}" class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold">{{ $booking->customer?->name ?? '—' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $serviceName($booking->service) }} · {{ $booking->staff?->display_name ?? __('Auto assigned') }}</p>
                            </div>
                            <div class="text-start sm:text-end">
                                <p class="font-semibold">{{ $booking->starts_at->setTimezone($timezone)->format('H:i') }} — {{ $booking->ends_at->setTimezone($timezone)->format('H:i') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $statusLabel($booking->status->value) }} · {{ $statusLabel($booking->payment_status->value) }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-12 text-center text-sm text-slate-500">{{ __('No bookings for this day.') }}</div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
@endsection
