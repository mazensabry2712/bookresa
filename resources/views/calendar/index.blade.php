@extends('layouts.dashboard')

@section('title', __('app.calendar_ui.calendar').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.calendar_ui.calendar'))

@section('content')
    @php
        $serviceName = static fn ($service): string => (string) (
            data_get($service?->name, app()->getLocale())
            ?? data_get($service?->name, 'en')
            ?? data_get($service?->name, 'ar')
            ?? '—'
        );

        $statusLabels = [
            'pending' => __('app.calendar_ui.status_pending'),
            'confirmed' => __('app.calendar_ui.status_confirmed'),
            'rescheduled' => __('app.calendar_ui.status_rescheduled'),
            'completed' => __('app.calendar_ui.status_completed'),
            'cancelled' => __('app.calendar_ui.status_cancelled'),
            'no_show' => __('app.calendar_ui.status_no_show'),
        ];

        $statusClasses = [
            'pending' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
            'confirmed' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-200',
            'rescheduled' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-200',
            'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200',
            'cancelled' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
            'no_show' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200',
        ];

        $paymentLabels = [
            'unpaid' => __('app.calendar_ui.payment_unpaid'),
            'partially_paid' => __('app.calendar_ui.payment_partial'),
            'paid' => __('app.calendar_ui.payment_paid'),
            'refunded' => __('app.calendar_ui.payment_refunded'),
        ];

        $dayLabels = [
            1 => __('app.calendar_ui.mon'),
            2 => __('app.calendar_ui.tue'),
            3 => __('app.calendar_ui.wed'),
            4 => __('app.calendar_ui.thu'),
            5 => __('app.calendar_ui.fri'),
            6 => __('app.calendar_ui.sat'),
            7 => __('app.calendar_ui.sun'),
        ];

        $viewLabels = [
            'day' => __('app.calendar_ui.day'),
            'week' => __('app.calendar_ui.week'),
            'month' => __('app.calendar_ui.month'),
        ];

        $periodTitle = match ($viewMode) {
            'day' => $reference->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY'),
            'week' => $periodStart->locale(app()->getLocale())->isoFormat('D MMMM').' — '.$periodEnd->locale(app()->getLocale())->isoFormat('D MMMM YYYY'),
            default => $reference->locale(app()->getLocale())->isoFormat('MMMM YYYY'),
        };
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }} · {{ $timezone }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ $periodTitle }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.calendar_ui.page_help') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @foreach ($viewLabels as $key => $label)
                    <a href="{{ route('calendar.index', ['view' => $key, 'date' => $reference->format('Y-m-d'), 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                       class="inline-flex min-h-10 items-center justify-center rounded-xl border px-3.5 py-2 text-sm font-bold {{ $viewMode === $key ? 'border-brand-indigo bg-indigo-50 text-brand-indigo dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="br-panel p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-2">
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $previousDate->format('Y-m-d'), 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                       class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        ← {{ __('app.calendar_ui.previous') }}
                    </a>
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $today]) }}"
                       class="inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-navy px-3.5 py-2 text-sm font-bold text-white hover:bg-slate-800">
                        {{ __('app.calendar_ui.today') }}
                    </a>
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $nextDate->format('Y-m-d'), 'service_id' => request('service_id'), 'staff_id' => request('staff_id'), 'status' => request('status')]) }}"
                       class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.calendar_ui.next') }} →
                    </a>
                </div>

                <p class="text-sm font-semibold text-slate-500">{{ $periodStart->format('d M Y') }} — {{ $periodEnd->format('d M Y') }}</p>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-4">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                <input type="hidden" name="date" value="{{ $reference->format('Y-m-d') }}">

                <label>
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.calendar_ui.service') }}</span>
                    <select name="service_id" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('app.calendar_ui.all_services') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) request('service_id') === (string) $service->id)>{{ $serviceName($service) }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.calendar_ui.staff') }}</span>
                    <select name="staff_id" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('app.calendar_ui.all_staff') }}</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>{{ $member->display_name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.calendar_ui.status') }}</span>
                    <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('app.calendar_ui.all_statuses') }}</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>{{ $statusLabels[$statusOption->value] ?? $statusOption->value }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end">
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                        {{ __('app.calendar_ui.apply_filters') }}
                    </button>
                </div>
            </form>
        </section>

        @if ($viewMode === 'month')
            <section class="br-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <div class="min-w-[860px]">
                        <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-800">
                            @foreach ($dayLabels as $dayLabel)
                                <div class="bg-slate-50 px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.12em] text-slate-400 dark:bg-slate-950/60">{{ $dayLabel }}</div>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-7">
                            @foreach ($calendarDays as $day)
                                @php $dayBookings = $bookings->filter(fn ($booking) => $booking->starts_at->setTimezone($timezone)->toDateString() === $day->toDateString()); @endphp
                                <div class="min-h-40 border-b border-slate-200 p-2.5 dark:border-slate-800 {{ $day->toDateString() === $today ? 'bg-indigo-50/50 dark:bg-indigo-950/20' : '' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $day->format('Y-m-d')]) }}"
                                           class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold {{ $day->toDateString() === $today ? 'bg-brand-indigo text-white' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                                            {{ $day->day }}
                                        </a>
                                        @if ($dayBookings->isNotEmpty())
                                            <span class="text-[11px] font-bold text-slate-400">{{ $dayBookings->count() }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-2 space-y-1.5">
                                        @foreach ($dayBookings->take(4) as $booking)
                                            @php $bookingStatus = $booking->status->value; @endphp
                                            <a href="{{ route('booking.management.show', $booking) }}"
                                               class="block rounded-xl border border-slate-200 bg-white px-2.5 py-2 transition hover:border-indigo-200 hover:bg-indigo-50/30 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-900 dark:hover:bg-indigo-950/20">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-[11px] font-extrabold text-brand-indigo">{{ $booking->starts_at->setTimezone($timezone)->format('H:i') }}</span>
                                                    <span class="h-2 w-2 shrink-0 rounded-full bg-brand-coral" aria-hidden="true"></span>
                                                </div>
                                                <p class="mt-1 truncate text-xs font-bold text-slate-950 dark:text-white">{{ $booking->customer?->name ?? '—' }}</p>
                                                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $serviceName($booking->service) }}</p>
                                                <p class="mt-1 truncate font-mono text-[10px] text-slate-400">{{ $booking->booking_reference }}</p>
                                                <span class="mt-1.5 inline-flex max-w-full truncate rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $statusClasses[$bookingStatus] ?? $statusClasses['pending'] }}">
                                                    {{ $statusLabels[$bookingStatus] ?? $bookingStatus }}
                                                </span>
                                            </a>
                                        @endforeach

                                        @if ($dayBookings->count() > 4)
                                            <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $day->format('Y-m-d')]) }}"
                                               class="block px-1 text-[11px] font-bold text-brand-indigo hover:underline">
                                                +{{ $dayBookings->count() - 4 }} {{ __('app.calendar_ui.more') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @elseif ($viewMode === 'week')
            <section class="br-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <div class="grid min-w-[1050px] grid-cols-7 divide-x divide-slate-200 dark:divide-slate-800">
                        @foreach ($calendarDays as $day)
                            @php $dayBookings = $bookings->filter(fn ($booking) => $booking->starts_at->setTimezone($timezone)->toDateString() === $day->toDateString()); @endphp
                            <div class="{{ $day->toDateString() === $today ? 'bg-indigo-50/40 dark:bg-indigo-950/15' : '' }}">
                                <div class="border-b border-slate-200 px-3 py-4 dark:border-slate-800">
                                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ $dayLabels[$day->dayOfWeekIso] }}</p>
                                    <p class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ $day->format('d') }}</p>
                                    <p class="text-xs text-slate-500">{{ $day->format('M') }}</p>
                                </div>

                                <div class="min-h-[28rem] space-y-2 p-2.5">
                                    @forelse ($dayBookings as $booking)
                                        @php $bookingStatus = $booking->status->value; @endphp
                                        <a href="{{ route('booking.management.show', $booking) }}"
                                           class="block rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-indigo-200 hover:shadow dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900">
                                            <p class="text-xs font-extrabold text-brand-indigo">
                                                {{ $booking->starts_at->setTimezone($timezone)->format('H:i') }}
                                                <span class="font-medium text-slate-400">— {{ $booking->ends_at->setTimezone($timezone)->format('H:i') }}</span>
                                            </p>
                                            <p class="mt-2 truncate text-sm font-bold text-slate-950 dark:text-white">{{ $booking->customer?->name ?? '—' }}</p>
                                            <p class="mt-1 truncate text-xs text-slate-500">{{ $serviceName($booking->service) }}</p>
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $statusClasses[$bookingStatus] ?? $statusClasses['pending'] }}">{{ $statusLabels[$bookingStatus] ?? $bookingStatus }}</span>
                                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-800">{{ $paymentLabels[$booking->payment_status->value] ?? $booking->payment_status->value }}</span>
                                            </div>
                                        </a>
                                    @empty
                                        <p class="py-10 text-center text-xs font-medium text-slate-400">{{ __('app.calendar_ui.no_bookings') }}</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            @php $dayBookings = $bookings->filter(fn ($booking) => $booking->starts_at->setTimezone($timezone)->toDateString() === $reference->toDateString()); @endphp
            <section class="br-panel overflow-hidden">
                <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-5 dark:border-slate-800">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.calendar_ui.day_schedule') }}</p>
                    <h3 class="text-xl font-bold text-slate-950 dark:text-white">{{ $reference->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY') }}</h3>
                </div>

                @forelse ($dayBookings as $booking)
                    @php $bookingStatus = $booking->status->value; @endphp
                    <a href="{{ route('booking.management.show', $booking) }}"
                       class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 transition last:border-b-0 hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:hover:bg-slate-950/30">
                        <div class="flex items-start gap-4">
                            <div class="w-16 shrink-0">
                                <p class="text-sm font-extrabold text-brand-indigo">{{ $booking->starts_at->setTimezone($timezone)->format('H:i') }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $booking->ends_at->setTimezone($timezone)->format('H:i') }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-slate-950 dark:text-white">{{ $booking->customer?->name ?? '—' }}</p>
                                <p class="mt-1 truncate text-sm text-slate-500">{{ $serviceName($booking->service) }} · {{ $booking->staff?->display_name ?? __('app.calendar_ui.auto_assigned') }}</p>
                                <p class="mt-1 font-mono text-xs text-slate-400">{{ $booking->booking_reference }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$bookingStatus] ?? $statusClasses['pending'] }}">{{ $statusLabels[$bookingStatus] ?? $bookingStatus }}</span>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $paymentLabels[$booking->payment_status->value] ?? $booking->payment_status->value }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-14 text-center">
                        <p class="font-bold text-slate-950 dark:text-white">{{ __('app.calendar_ui.no_bookings') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('app.calendar_ui.no_day_bookings_help') }}</p>
                    </div>
                @endforelse
            </section>
        @endif
    </div>
@endsection
