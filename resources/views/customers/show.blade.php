@extends('layouts.dashboard')

@section('title', $customer->name.' — '.__('app.customer_ui.customer'))
@section('heading', __('app.customer_ui.customer'))

@section('content')
    @php
        $serviceName = static fn ($service): string => (string) (
            data_get($service?->name, app()->getLocale())
            ?? data_get($service?->name, 'en')
            ?? data_get($service?->name, 'ar')
            ?? '—'
        );

        $statusLabels = [
            'pending' => __('app.customer_ui.status_pending'),
            'confirmed' => __('app.customer_ui.status_confirmed'),
            'rescheduled' => __('app.customer_ui.status_rescheduled'),
            'completed' => __('app.customer_ui.status_completed'),
            'cancelled' => __('app.customer_ui.status_cancelled'),
            'no_show' => __('app.customer_ui.status_no_show'),
        ];

        $paymentLabels = [
            'unpaid' => __('app.customer_ui.payment_unpaid'),
            'partially_paid' => __('app.customer_ui.payment_partial'),
            'paid' => __('app.customer_ui.payment_paid'),
            'refunded' => __('app.customer_ui.payment_refunded'),
        ];

        $usageLimit = (int) ($usageSummary?->includedCustomerLimit ?? 0);
        $usagePercent = (int) ($usagePercent ?? 0);
    @endphp

    <div class="space-y-6">
        <section>
            <a href="{{ route('customers.index') }}" class="text-sm font-bold text-slate-500 hover:text-brand-indigo"><span class="br-direction-arrow" aria-hidden="true">←</span> {{ __('app.customer_ui.back_to_customers') }}</a>
            <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-lg font-extrabold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">
                        {{ str($customer->name)->substr(0, 1)->upper() }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ $customer->name }}</h2>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                            <span>{{ $customer->phone ?? '—' }}</span>
                            <span>{{ $customer->email ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="br-panel p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.customer_ui.last_seen') }}</p>
                    <p class="mt-1 text-sm font-bold text-slate-950 dark:text-white">{{ $customer->last_seen_at?->diffForHumans() ?? '—' }}</p>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                [__('app.customer_ui.total_bookings'), $metrics['totalBookings']],
                [__('app.customer_ui.completed'), $metrics['completedBookings']],
                [__('app.customer_ui.cancelled'), $metrics['cancelledBookings']],
                [__('app.customer_ui.no_shows'), $metrics['noShows']],
                [__('app.customer_ui.total_spent'), number_format($metrics['totalSpentMinor'] / 100, 2).' '.($customer->bookings->first()?->service?->currency ?? 'EGP')],
            ] as [$label, $value])
                <article class="br-panel p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $value }}</p>
                </article>
            @endforeach
        </section>

        @if ($upcomingBooking)
            <section class="br-panel p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.customer_ui.upcoming_booking') }}</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ $serviceName($upcomingBooking->service) }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $upcomingBooking->starts_at->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') }} · {{ $upcomingBooking->staff?->display_name ?? __('app.customer_ui.auto_assigned') }}</p>
                    </div>
                    <a href="{{ route('booking.management.show', $upcomingBooking) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.customer_ui.open_booking') }}
                    </a>
                </div>
            </section>
        @endif

        @if ($usageSummary)
            <section class="br-panel p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.customer_ui.customer_usage') }}</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">
                            {{ number_format($usageSummary->uniqueCustomerCount) }} / {{ number_format($usageSummary->includedCustomerLimit) }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('app.customer_ui.usage_help') }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-2xl font-extrabold text-slate-950 dark:text-white">{{ $usagePercent }}%</p>
                        @if ($usageSummary->additionalCustomerCount > 0)
                            <p class="mt-1 text-xs font-bold text-amber-700 dark:text-amber-300">{{ __('app.customer_ui.over_limit') }}</p>
                        @endif
                    </div>
                </div>
                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-brand-indigo" style="width: {{ $usagePercent }}%"></div>
                </div>
                <p class="mt-3 text-xs text-slate-500">{{ trans_choice('app.customer_ui.workspace_customer_count', $usageSummary->uniqueCustomerCount, ['count' => $usageSummary->uniqueCustomerCount]) }}</p>
            </section>
        @endif

        <section class="br-panel p-5 sm:p-6">
            <div>
                <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.customer_ui.customer_details') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('app.customer_ui.details_help') }}</p>
            </div>

            @can('customers.update')
                <form method="POST" action="{{ route('customers.update', $customer) }}" class="mt-5 grid gap-4 md:grid-cols-3">
                    @csrf
                    @method('PUT')
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('app.customer_ui.name') }}</span>
                        <input name="name" value="{{ old('name', $customer->name) }}" required maxlength="160" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('app.customer_ui.phone') }}</span>
                        <input name="phone" type="tel" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('app.customer_ui.email') }}</span>
                        <input name="email" type="email" value="{{ old('email', $customer->email) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-navy px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">{{ __('app.customer_ui.save_changes') }}</button>
                    </div>
                </form>
            @else
                <dl class="mt-5 grid gap-4 md:grid-cols-3">
                    <div><dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.customer_ui.name') }}</dt><dd class="mt-1 font-semibold">{{ $customer->name }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.customer_ui.phone') }}</dt><dd class="mt-1 font-semibold">{{ $customer->phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.customer_ui.email') }}</dt><dd class="mt-1 font-semibold">{{ $customer->email ?? '—' }}</dd></div>
                </dl>
            @endcan
        </section>

        <section class="br-panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.customer_ui.booking_history') }}</h3>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($bookings as $booking)
                    <a href="{{ route('booking.management.show', $booking) }}" class="block p-5 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-950 dark:text-white">{{ $serviceName($booking->service) }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $booking->starts_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') }} · {{ $booking->staff?->display_name ?? __('app.customer_ui.auto_assigned') }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $statusLabels[$booking->status->value] ?? $booking->status->value }}</span>
                                <span class="rounded-full border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $paymentLabels[$booking->payment_status->value] ?? $booking->payment_status->value }}</span>
                            </div>
                        </div>
                        <p class="mt-2 font-mono text-xs text-slate-400">{{ $booking->booking_reference }}</p>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.customer_ui.no_booking_history') }}</div>
                @endforelse
            </div>

            @if ($bookings->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $bookings->links() }}</div>
            @endif
        </section>

        @can('billing.view')
            <section class="br-panel overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.customer_ui.payment_history') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.customer_ui.payment_history_help') }}</p>
                </div>
                @if ($paymentHistory)
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($paymentHistory as $payment)
                            @php $paymentBooking = $payment->payable; @endphp
                            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-mono text-xs font-bold text-slate-400">{{ $payment->reference }}</p>
                                    <p class="mt-1 font-semibold text-slate-950 dark:text-white">{{ $paymentBooking?->booking_reference ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $payment->paid_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') ?? $payment->created_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') }}</p>
                                </div>
                                <div class="text-start sm:text-end">
                                    <p class="font-bold text-slate-950 dark:text-white">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $paymentLabels[$payment->status->value] ?? $payment->status->value }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.customer_ui.no_payment_history') }}</div>
                        @endforelse
                    </div>
                    @if ($paymentHistory->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $paymentHistory->links() }}</div>
                    @endif
                @else
                    <div class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.customer_ui.payment_history_unavailable') }}</div>
                @endif
            </section>
        @endcan
    </div>
@endsection
