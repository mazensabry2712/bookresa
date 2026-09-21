@extends('layouts.dashboard')

@section('title', __('Booking').' '.$booking->booking_reference)
@section('heading', __('Booking details'))

@section('content')
    @php
        $serviceName = data_get($booking->service?->name, app()->getLocale())
            ?? data_get($booking->service?->name, 'en')
            ?? '—';
        $statusLabel = fn ($value) => str($value)->replace('_', ' ')->title();
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('booking.management.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    ← {{ __('Back to bookings') }}
                </a>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight">{{ $booking->booking_reference }}</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        {{ $statusLabel($booking->status->value) }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $booking->starts_at?->setTimezone($timezone)->format('l, d M Y · H:i') }}
                    — {{ $booking->ends_at?->setTimezone($timezone)->format('H:i') }}
                    · {{ $timezone }}
                </p>
            </div>

            <div class="rounded-2xl bg-white px-4 py-3 text-end shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Payment') }}</p>
                <p class="mt-1 font-semibold">{{ str($booking->payment_status->value)->replace('_', ' ')->title() }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="space-y-6 xl:col-span-2">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h3 class="font-semibold">{{ __('Appointment') }}</h3>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Service') }}</dt>
                            <dd class="mt-1">{{ $serviceName }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Staff') }}</dt>
                            <dd class="mt-1">{{ $booking->staff?->display_name ?? __('Auto assigned') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Duration') }}</dt>
                            <dd class="mt-1">{{ $booking->service?->duration_minutes }} {{ __('minutes') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Price') }}</dt>
                            <dd class="mt-1">{{ number_format(($booking->service?->price_minor ?? 0) / 100, 2) }} {{ $booking->service?->currency }}</dd>
                        </div>
                    </dl>

                    @if ($booking->notes)
                        <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-950/50">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</p>
                            <p class="mt-1 whitespace-pre-line">{{ $booking->notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h3 class="font-semibold">{{ __('Customer') }}</h3>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</dt>
                            <dd class="mt-1">{{ $booking->customer?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Phone') }}</dt>
                            <dd class="mt-1">{{ $booking->customer?->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Email') }}</dt>
                            <dd class="mt-1">{{ $booking->customer?->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created') }}</dt>
                            <dd class="mt-1">{{ $booking->created_at?->setTimezone($timezone)->format('d M Y, H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h3 class="font-semibold">{{ __('Status history') }}</h3>
                    <div class="mt-4 space-y-4">
                        @forelse ($booking->statusHistory as $history)
                            <div class="flex gap-3">
                                <div class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-slate-400"></div>
                                <div class="min-w-0">
                                    <p class="text-sm">
                                        <span class="font-semibold">{{ $statusLabel($history->to_status) }}</span>
                                        @if ($history->from_status)
                                            <span class="text-slate-500">({{ $statusLabel($history->from_status) }})</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $history->created_at?->setTimezone($timezone)->format('d M Y, H:i') }}
                                        @if ($history->changedBy)
                                            · {{ $history->changedBy->name }}
                                        @endif
                                    </p>
                                    @if ($history->reason)
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $history->reason }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('No status history recorded.') }}</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h3 class="font-semibold">{{ __('Manage booking') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Only valid lifecycle transitions are accepted by the backend.') }}</p>

                    <div class="mt-5 space-y-3">
                        @if ($booking->status->value === 'pending')
                            @can('bookings.update')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="confirmed">
                                    <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                                        {{ __('Confirm booking') }}
                                    </button>
                                </form>
                            @endcan
                            @can('bookings.cancel')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <input type="text" name="reason" maxlength="500" placeholder="{{ __('Cancellation reason (optional)') }}"
                                           class="mb-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <button class="w-full rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                        {{ __('Cancel booking') }}
                                    </button>
                                </form>
                            @endcan
                        @elseif ($booking->status->value === 'confirmed')
                            @can('bookings.complete')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="completed">
                                    <button class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                        {{ __('Mark completed') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="no_show">
                                    <button class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                        {{ __('Mark no-show') }}
                                    </button>
                                </form>
                            @endcan
                            @can('bookings.cancel')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <input type="text" name="reason" maxlength="500" placeholder="{{ __('Cancellation reason (optional)') }}"
                                           class="mb-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <button class="w-full rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                        {{ __('Cancel booking') }}
                                    </button>
                                </form>
                            @endcan
                        @elseif ($booking->status->value === 'rescheduled')
                            @can('bookings.update')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="confirmed">
                                    <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                                        {{ __('Confirm rescheduled booking') }}
                                    </button>
                                </form>
                            @endcan
                            @can('bookings.cancel')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="w-full rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                        {{ __('Cancel booking') }}
                                    </button>
                                </form>
                            @endcan
                        @else
                            <p class="rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-600 dark:bg-slate-950/50 dark:text-slate-300">
                                {{ __('This booking has reached a terminal status.') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h3 class="font-semibold">{{ __('Reference') }}</h3>
                    <p class="mt-2 break-all font-mono text-sm">{{ $booking->booking_reference }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
