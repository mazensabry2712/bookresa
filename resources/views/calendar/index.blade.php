@extends('layouts.dashboard')

@section('title', __('Calendar').' — '.config('app.name', 'BookResa'))
@section('heading', __('Calendar'))

@section('content')
    @php
        $statusLabel = fn ($value) => str($value)->replace('_', ' ')->title();
        $serviceName = fn ($service) => data_get($service?->name, app()->getLocale())
            ?? data_get($service?->name, 'en')
            ?? '—';

        $statusClasses = [
            'pending' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
            'confirmed' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300',
            'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300',
            'cancelled' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300',
            'rescheduled' => 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300',
            'no_show' => 'border-slate-200 bg-slate-100 text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ];

        $weekdayLabels = [
            1 => __('Mon'),
            2 => __('Tue'),
            3 => __('Wed'),
            4 => __('Thu'),
            5 => __('Fri'),
            6 => __('Sat'),
            7 => __('Sun'),
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ $tenant->slug }} · {{ $timezone }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ $month->format('F Y') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Bookings by day, service, staff and status.') }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('calendar.index', ['month' => $previousMonth, 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                   class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    ← {{ __('Previous') }}
                </a>
                <a href="{{ route('calendar.index', ['month' => now($timezone)->format('Y-m')]) }}"
                   class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                    {{ __('Today') }}
                </a>
                <a href="{{ route('calendar.index', ['month' => $nextMonth, 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                   class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('Next') }} →
                </a>
            </div>
        </div>

        <form method="GET" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">

            <div class="grid gap-3 sm:grid-cols-3">
                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Service') }}</span>
                    <select name="service_id" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All services') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) request('service_id') === (string) $service->id)>
                                {{ $serviceName($service) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Staff') }}</span>
                    <select name="staff_id" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All staff') }}</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>
                                {{ $member->display_name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</span>
                    <select name="status" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ $statusLabel($status->value) }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                    {{ __('Apply filters') }}
                </button>
                <a href="{{ route('calendar.index', ['month' => $month->format('Y-m')]) }}"
                   class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="grid grid-cols-7 divide-x divide-slate-200 border-b border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                @foreach ($weekdayLabels as $label)
                    <div class="bg-slate-50 px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-950/50">
                        {{ $label }}
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 divide-x divide-y divide-slate-200 dark:divide-slate-800">
                @foreach ($calendarDays as $day)
                    @php
                        $dateKey = $day->toDateString();
                        $dayBookings = $bookingsByDate->get($dateKey, collect());
                        $isCurrentMonth = $day->format('Y-m') === $month->format('Y-m');
                        $isToday = $dateKey === $today;
                    @endphp

                    <div class="min-h-36 p-2 {{ $isCurrentMonth ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/80 dark:bg-slate-950/30' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="{{ $isToday ? 'inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white dark:bg-white dark:text-slate-900' : 'text-sm font-semibold '.($isCurrentMonth ? 'text-slate-900 dark:text-white' : 'text-slate-400') }}">
                                {{ $day->day }}
                            </span>
                            @if ($dayBookings->isNotEmpty())
                                <span class="text-[11px] font-medium text-slate-400">{{ $dayBookings->count() }}</span>
                            @endif
                        </div>

                        <div class="mt-2 space-y-1.5">
                            @foreach ($dayBookings->take(5) as $booking)
                                @php
                                    $bookingStatus = $booking->status->value;
                                    $bookingClass = $statusClasses[$bookingStatus] ?? $statusClasses['pending'];
                                    $starts = $booking->starts_at->setTimezone($timezone);
                                    $ends = $booking->ends_at->setTimezone($timezone);
                                @endphp

                                <a href="{{ route('booking.management.show', $booking) }}"
                                   class="block rounded-lg border px-2 py-1.5 text-left text-[11px] leading-tight transition hover:-translate-y-px hover:shadow-sm {{ $bookingClass }}">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="font-bold">{{ $starts->format('H:i') }}</span>
                                        <span class="truncate opacity-70">{{ $statusLabel($bookingStatus) }}</span>
                                    </div>
                                    <div class="mt-1 truncate font-semibold">
                                        {{ $booking->customer?->name ?? '—' }}
                                    </div>
                                    <div class="mt-0.5 truncate font-mono text-[10px] opacity-70">
                                        {{ $booking->booking_reference }}
                                    </div>
                                    <div class="mt-0.5 truncate opacity-80">
                                        {{ $serviceName($booking->service) }}
                                    </div>
                                    <div class="mt-0.5 truncate opacity-70">
                                        {{ $booking->staff?->display_name ?? __('Auto assigned') }}
                                    </div>
                                    <div class="mt-0.5 truncate opacity-70">
                                        {{ $starts->format('H:i') }}–{{ $ends->format('H:i') }} · {{ $booking->payment_status->value }}
                                    </div>
                                </a>
                            @endforeach

                            @if ($dayBookings->count() > 5)
                                <a href="{{ route('booking.management.index', ['date' => $dateKey]) }}"
                                   class="block rounded-lg px-2 py-1 text-center text-[11px] font-semibold text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                                    +{{ $dayBookings->count() - 5 }} {{ __('more') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
