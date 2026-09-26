@extends('layouts.dashboard')

@section('title', __('app.booking_ui.bookings').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.booking_ui.bookings'))

@section('content')
    @php
        $serviceName = static fn ($service): string => (string) (
            data_get($service?->name, app()->getLocale())
            ?? data_get($service?->name, 'en')
            ?? data_get($service?->name, 'ar')
            ?? '—'
        );

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
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.booking_ui.booking_management') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.booking_ui.page_help') }}</p>
            </div>

            <a href="{{ route('public.booking.canonical.show', ['tenant' => $tenant->slug]) }}"
               target="_blank"
               rel="noreferrer"
               class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-600">
                {{ __('app.booking_ui.new_booking') }}
            </a>
        </section>

        <section class="grid gap-3 sm:grid-cols-3" aria-label="{{ __('app.booking_ui.summary') }}">
            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.total_results') }}</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ number_format($bookings->total()) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.booking_ui.current_filter_result') }}</p>
            </article>
            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.date_filter') }}</p>
                <p class="mt-2 truncate text-lg font-bold text-slate-950 dark:text-white">{{ request('date') ?: __('app.booking_ui.all_dates') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $timezone }}</p>
            </article>
            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.status_filter') }}</p>
                <p class="mt-2 truncate text-lg font-bold text-slate-950 dark:text-white">{{ request('status') ? ($statusLabels[request('status')] ?? request('status')) : __('app.booking_ui.all_statuses') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.booking_ui.filter_context') }}</p>
            </article>
        </section>

        <section class="br-panel p-4 sm:p-5">
            <form method="GET" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-6">
                    <label class="lg:col-span-2">
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.search') }}</span>
                        <input name="search" value="{{ request('search') }}"
                               placeholder="{{ __('app.booking_ui.search_placeholder') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </label>

                    <label>
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.status') }}</span>
                        <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="">{{ __('app.booking_ui.all_statuses') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $statusLabels[$status->value] ?? str($status->value)->headline() }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.service') }}</span>
                        <select name="service_id" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="">{{ __('app.booking_ui.all_services') }}</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected((string) request('service_id') === (string) $service->id)>{{ $serviceName($service) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.staff') }}</span>
                        <select name="staff_id" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="">{{ __('app.booking_ui.all_staff') }}</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>{{ $member->display_name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.booking_ui.date') }}</span>
                        <input type="date" name="date" value="{{ request('date') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </label>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">
                        {{ __('app.booking_ui.apply_filters') }}
                    </button>
                    <a href="{{ route('booking.management.index') }}"
                       class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.booking_ui.reset') }}
                    </a>
                </div>
            </form>
        </section>

        <section class="br-panel overflow-hidden">
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/60">
                        <tr class="text-start text-xs font-bold uppercase tracking-[0.12em] text-slate-400">
                            <th class="px-5 py-4">{{ __('app.booking_ui.reference') }}</th>
                            <th class="px-5 py-4">{{ __('app.booking_ui.customer') }}</th>
                            <th class="px-5 py-4">{{ __('app.booking_ui.service') }}</th>
                            <th class="px-5 py-4">{{ __('app.booking_ui.staff') }}</th>
                            <th class="px-5 py-4">{{ __('app.booking_ui.starts') }}</th>
                            <th class="px-5 py-4">{{ __('app.booking_ui.status') }}</th>
                            <th class="px-5 py-4">{{ __('app.booking_ui.payment') }}</th>
                            <th class="px-5 py-4 text-end">{{ __('app.booking_ui.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($bookings as $booking)
                            @php
                                $status = $booking->status->value;
                                $paymentStatus = $booking->payment_status->value;
                            @endphp
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                                <td class="px-5 py-4 font-mono text-xs font-bold text-slate-700 dark:text-slate-200">{{ $booking->booking_reference }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950 dark:text-white">{{ $booking->customer?->name ?? '—' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $booking->customer?->phone ?? $booking->customer?->email ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-4 font-medium">{{ $serviceName($booking->service) }}</td>
                                <td class="px-5 py-4">{{ $booking->staff?->display_name ?? __('app.booking_ui.auto_assigned') }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    {{ $booking->starts_at?->setTimezone($timezone)->format('d M Y, H:i') }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$status] ?? $statusClasses['pending'] }}">{{ $statusLabels[$status] ?? $status }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $paymentClasses[$paymentStatus] ?? $paymentClasses['unpaid'] }}">{{ $paymentLabels[$paymentStatus] ?? $paymentStatus }}</span>
                                </td>
                                <td class="px-5 py-4 text-end">
                                    <a href="{{ route('booking.management.show', $booking) }}" class="font-bold text-brand-indigo hover:underline">{{ __('app.booking_ui.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-16 text-center">
                                    <p class="font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.no_bookings') }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ __('app.booking_ui.no_bookings_help') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-200 md:hidden dark:divide-slate-800">
                @forelse ($bookings as $booking)
                    @php
                        $status = $booking->status->value;
                        $paymentStatus = $booking->payment_status->value;
                    @endphp
                    <a href="{{ route('booking.management.show', $booking) }}" class="block p-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-mono text-xs font-bold text-slate-500">{{ $booking->booking_reference }}</p>
                                <h3 class="mt-1 font-bold text-slate-950 dark:text-white">{{ $booking->customer?->name ?? '—' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $serviceName($booking->service) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$status] ?? $statusClasses['pending'] }}">{{ $statusLabels[$status] ?? $status }}</span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                            <div class="rounded-xl br-surface-soft p-3">
                                <span class="block font-semibold text-slate-400">{{ __('app.booking_ui.starts') }}</span>
                                <span class="mt-1 block font-bold text-slate-900 dark:text-white">{{ $booking->starts_at?->setTimezone($timezone)->format('d M, H:i') }}</span>
                            </div>
                            <div class="rounded-xl br-surface-soft p-3">
                                <span class="block font-semibold text-slate-400">{{ __('app.booking_ui.payment') }}</span>
                                <span class="mt-1 block font-bold text-slate-900 dark:text-white">{{ $paymentLabels[$paymentStatus] ?? $paymentStatus }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-14 text-center">
                        <p class="font-bold text-slate-950 dark:text-white">{{ __('app.booking_ui.no_bookings') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('app.booking_ui.no_bookings_help') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($bookings->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                    {{ $bookings->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
