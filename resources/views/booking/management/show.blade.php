@extends('layouts.dashboard')

@section('title', __('app.booking_ui.booking').' '.$booking->booking_reference)
@section('heading', __('app.booking_ui.booking_details'))

@section('content')
    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $status = $booking->status->value;
        $paymentStatus = $booking->payment_status->value;

        $statusLabels = [
            'pending' => __('app.booking_ui.status_pending'),
            'confirmed' => __('app.booking_ui.status_confirmed'),
            'rescheduled' => __('app.booking_ui.status_rescheduled'),
            'completed' => __('app.booking_ui.status_completed'),
            'cancelled' => __('app.booking_ui.status_cancelled'),
            'no_show' => __('app.booking_ui.status_no_show'),
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
            'unpaid' => __('app.booking_ui.payment_unpaid'),
            'partially_paid' => __('app.booking_ui.payment_partial'),
            'paid' => __('app.booking_ui.payment_paid'),
            'refunded' => __('app.booking_ui.payment_refunded'),
        ];

        $paymentClasses = [
            'unpaid' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200',
            'partially_paid' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
            'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200',
            'refunded' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ];

        $startsAt = $booking->starts_at?->setTimezone($timezone);
        $endsAt = $booking->ends_at?->setTimezone($timezone);
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('booking.management.index') }}" class="text-sm font-bold text-slate-500 hover:text-brand-indigo">
                    ← {{ __('app.booking_ui.back_to_bookings') }}
                </a>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <h2 class="truncate text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ $booking->booking_reference }}</h2>
                    <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$status] ?? $statusClasses['pending'] }}">
                        {{ $statusLabels[$status] ?? $status }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-slate-500">
                    {{ $startsAt?->isoFormat('dddd, D MMMM YYYY · HH:mm') }}
                    — {{ $endsAt?->format('H:i') }} · {{ $timezone }}
                </p>
            </div>

            <div class="br-panel min-w-48 p-4">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.payment') }}</p>
                <span class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $paymentClasses[$paymentStatus] ?? $paymentClasses['unpaid'] }}">
                    {{ $paymentLabels[$paymentStatus] ?? $paymentStatus }}
                </span>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(18rem,0.8fr)]">
            <section class="space-y-6">
                <article class="br-panel p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.appointment') }}</p>
                            <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ $localized($booking->service?->name) }}</h3>
                        </div>
                        <span class="text-sm font-bold text-slate-500">{{ number_format(($booking->service?->price_minor ?? 0) / 100, 2) }} {{ $booking->service?->currency }}</span>
                    </div>

                    <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.booking_ui.customer') }}</dt>
                            <dd class="mt-1 font-semibold text-slate-950 dark:text-white">{{ $booking->customer?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.booking_ui.staff') }}</dt>
                            <dd class="mt-1 font-semibold text-slate-950 dark:text-white">{{ $booking->staff?->display_name ?? __('app.booking_ui.auto_assigned') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.booking_ui.starts') }}</dt>
                            <dd class="mt-1 font-semibold text-slate-950 dark:text-white">{{ $startsAt?->format('d M Y, H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.booking_ui.duration') }}</dt>
                            <dd class="mt-1 font-semibold text-slate-950 dark:text-white">{{ $booking->service?->duration_minutes }} {{ __('app.booking_ui.minutes') }}</dd>
                        </div>
                    </dl>

                    @if ($booking->notes)
                        <div class="mt-6 rounded-xl br-surface-soft p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.booking_ui.notes') }}</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $booking->notes }}</p>
                        </div>
                    @endif
                </article>

                <article class="br-panel p-5 sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.customer') }}</p>
                    <div class="mt-4 flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300">
                            {{ str($booking->customer?->name ?? '?')->substr(0, 1)->upper() }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-slate-950 dark:text-white">{{ $booking->customer?->name ?? '—' }}</h3>
                            <div class="mt-2 space-y-1 text-sm text-slate-500">
                                <p>{{ $booking->customer?->phone ?? '—' }}</p>
                                <p>{{ $booking->customer?->email ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="br-panel p-5 sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.status_history') }}</p>
                    <div class="mt-5 space-y-4">
                        @forelse ($booking->statusHistory as $history)
                            <div class="flex gap-3">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-coral" aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $statusLabels[$history->to_status] ?? str($history->to_status)->headline() }}
                                        @if ($history->from_status)
                                            <span class="font-normal text-slate-400">← {{ $statusLabels[$history->from_status] ?? str($history->from_status)->headline() }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $history->created_at?->setTimezone($timezone)->format('d M Y, H:i') }}
                                        @if ($history->changedBy) · {{ $history->changedBy->name }} @endif
                                    </p>
                                    @if ($history->reason)
                                        <p class="mt-1.5 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $history->reason }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('app.booking_ui.no_status_history') }}</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="br-panel p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.manage_booking') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.allowed_actions') }}</h3>

                    <div class="mt-5 space-y-3">
                        @if ($status === 'pending')
                            @can('bookings.update')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.booking_ui.confirm') }}</button>
                                </form>
                            @endcan
                        @elseif ($status === 'confirmed')
                            @can('bookings.complete')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">{{ __('app.booking_ui.mark_completed') }}</button>
                                </form>
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="no_show">
                                    <button type="submit" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.booking_ui.mark_no_show') }}</button>
                                </form>
                            @endcan
                        @elseif ($status === 'rescheduled')
                            @can('bookings.update')
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.booking_ui.confirm') }}</button>
                                </form>
                            @endcan
                        @endif

                        @can('bookings.update')
                            @if (in_array($status, ['pending', 'confirmed', 'rescheduled'], true))
                                <a href="#reschedule"
                                   class="inline-flex w-full min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('app.booking_ui.reschedule') }}
                                </a>
                            @endif
                        @endcan

                        @can('bookings.cancel')
                            @if (in_array($status, ['pending', 'confirmed', 'rescheduled'], true))
                                <form method="POST" action="{{ route('booking.management.status', $booking) }}" onsubmit="return confirm(@json(__('app.booking_ui.cancel_confirm')))">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <label class="block">
                                        <span class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('app.booking_ui.cancellation_reason') }}</span>
                                        <input type="text" name="reason" maxlength="500" placeholder="{{ __('app.booking_ui.reason_optional') }}"
                                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    </label>
                                    <button type="submit" class="mt-2 w-full rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                        {{ __('app.booking_ui.cancel_booking') }}
                                    </button>
                                </form>
                            @endif
                        @endcan

                        @if (in_array($status, ['completed', 'cancelled', 'no_show'], true))
                            <p class="rounded-xl br-surface-soft px-4 py-3 text-sm leading-6 text-slate-500">{{ __('app.booking_ui.terminal_status') }}</p>
                        @endif
                    </div>
                </article>

                @can('bookings.update')
                    @if (in_array($status, ['pending', 'confirmed', 'rescheduled'], true))
                        <article id="reschedule" class="br-panel p-5">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.reschedule') }}</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.reschedule_title') }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.booking_ui.reschedule_help') }}</p>

                            <form method="POST" action="{{ route('booking.management.reschedule', $booking) }}" class="mt-5 space-y-3">
                                @csrf
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('app.booking_ui.date') }}</span>
                                    <input type="date" name="date" value="{{ old('date', $startsAt?->format('Y-m-d')) }}" required
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('app.booking_ui.time') }}</span>
                                    <input type="time" name="time" value="{{ old('time', $startsAt?->format('H:i')) }}" required
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold text-slate-500">{{ __('app.booking_ui.staff') }}</span>
                                    <select name="staff_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        <option value="">{{ __('app.booking_ui.keep_current_staff') }}</option>
                                        @foreach ($staffMembers as $member)
                                            <option value="{{ $member->id }}" @selected($booking->staff_id === $member->id)>{{ $member->display_name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="submit" class="w-full rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">
                                    {{ __('app.booking_ui.save_reschedule') }}
                                </button>
                            </form>
                        </article>
                    @endif
                @endcan
            </aside>
        </div>
    </div>
@endsection
