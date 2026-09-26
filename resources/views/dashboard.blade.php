@extends('layouts.dashboard')

@section('title', __('app.dashboard').' — '.config('bookresa.name', 'BookResa'))
@section('heading', __('app.dashboard'))

@section('content')
    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $money = static fn (int $minor, string $currency = 'EGP'): string => number_format($minor / 100, 2).' '.$currency;

        $planName = $localized($subscription?->plan?->name);

        $statusLabels = [
            'pending' => __('dashboard_ui.status_pending'),
            'confirmed' => __('dashboard_ui.status_confirmed'),
            'rescheduled' => __('dashboard_ui.status_rescheduled'),
            'completed' => __('dashboard_ui.status_completed'),
            'cancelled' => __('dashboard_ui.status_cancelled'),
            'no_show' => __('dashboard_ui.status_no_show'),
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
            'unpaid' => __('dashboard_ui.payment_unpaid'),
            'partially_paid' => __('dashboard_ui.payment_partial'),
            'paid' => __('dashboard_ui.payment_paid'),
            'refunded' => __('dashboard_ui.payment_refunded'),
        ];

        $paymentClasses = [
            'unpaid' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200',
            'partially_paid' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
            'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200',
            'refunded' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ];

        $subscriptionStatusLabels = [
            'trial' => __('dashboard_ui.status_trial'),
            'active' => __('dashboard_ui.status_active'),
        ];

        $usageLimit = max((int) ($usageSummary?->includedCustomerLimit ?? 0), 1);
        $usagePercent = $attention['usagePercent'] ?? 0;
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-brand-indigo">{{ $todayLabel }}</p>
                <h2 class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    {{ __('dashboard_ui.welcome', ['name' => auth()->user()->name]) }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    {{ __('dashboard_ui.workspace_summary') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('bookings.view')
                    <a href="{{ route('booking.management.index') }}"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                        {{ __('dashboard_ui.open_bookings') }}
                    </a>
                @endcan

                <a href="{{ route('public.booking.canonical.show', ['tenant' => $tenant->slug]) }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('dashboard_ui.view_booking_page') }}
                </a>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('dashboard_ui.summary') }}">
            <article class="br-panel p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-brand-indigo dark:bg-indigo-950/40 dark:text-indigo-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3.5 10h17M8 14h3M8 18h6"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('dashboard_ui.today') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('dashboard_ui.today_bookings') }}</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ number_format($metrics['todayBookings']) }}</p>
            </article>

            <article class="br-panel p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 7h16M6.5 4.5h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-11a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" d="M8 12h2M8 16h5"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('dashboard_ui.paid') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('dashboard_ui.today_revenue') }}</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ $money($metrics['todayRevenueMinor']) }}</p>
            </article>

            <article class="br-panel p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M4 10h16M8 14h2M14 14h2M8 17h2"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('dashboard_ui.upcoming') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('dashboard_ui.upcoming_bookings') }}</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ number_format($metrics['upcomingBookings']) }}</p>
            </article>

            <article class="br-panel p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path stroke-linecap="round" d="M3.5 20a5.5 5.5 0 0 1 11 0M16 9a3 3 0 0 1 0 5.7M15.5 16a5 5 0 0 1 4.5 4"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('dashboard_ui.today') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('dashboard_ui.new_customers') }}</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ number_format($metrics['newCustomersToday']) }}</p>
            </article>
        </section>

        @if (
            $attention['pendingBookings'] > 0
            || $attention['unpaidBookings'] > 0
            || $attention['subscriptionNeedsAction']
            || $attention['usageOverLimit']
            || (($attention['usagePercent'] ?? 0) >= 80)
        )
        <section class="br-panel overflow-hidden" aria-labelledby="attention-title">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <h3 id="attention-title" class="font-semibold text-slate-950 dark:text-white">{{ __('dashboard_ui.attention') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard_ui.attention_description') }}</p>
                </div>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @if ($attention['pendingBookings'] > 0)
                    <a href="{{ route('booking.management.index') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ trans_choice('dashboard_ui.pending_bookings_count', $attention['pendingBookings']) }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ __('dashboard_ui.pending_bookings_help') }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('dashboard_ui.review') }}</span>
                    </a>
                @endif

                @if ($attention['unpaidBookings'] > 0)
                    <a href="{{ route('booking.management.index') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M6 4.5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2ZM8 9.5h8M8 13.5h4"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ trans_choice('dashboard_ui.unpaid_bookings_count', $attention['unpaidBookings']) }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ __('dashboard_ui.unpaid_bookings_help') }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('dashboard_ui.review') }}</span>
                    </a>
                @endif

                @can('billing.view')
                @if ($attention['subscriptionNeedsAction'])
                    <a href="{{ route('billing.subscription') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M7 12h10M8 16h6"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    @if ($subscription)
                                        {{ __('dashboard_ui.subscription_expires_soon') }}
                                    @else
                                        {{ __('dashboard_ui.no_active_subscription') }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    @if ($subscription)
                                        {{ trans_choice('dashboard_ui.subscription_days_left', $subscriptionDaysRemaining ?? 0, ['count' => $subscriptionDaysRemaining ?? 0]) }}
                                    @else
                                        {{ __('dashboard_ui.subscription_action_help') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('dashboard_ui.manage') }}</span>
                    </a>
                @endif

                @if ($attention['usageOverLimit'] || (($attention['usagePercent'] ?? 0) >= 80))
                    <a href="{{ route('billing.subscription') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M12 3.5 20 19H4l8-15.5Z"/><path stroke-linecap="round" d="M12 9v4.5M12 16.5v.2"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    @if ($attention['usageOverLimit'])
                                        {{ __('dashboard_ui.usage_over_limit') }}
                                    @else
                                        {{ __('dashboard_ui.usage_high') }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    {{ __('dashboard_ui.usage_help', ['percent' => $attention['usagePercent'] ?? 0]) }}
                                </p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('dashboard_ui.manage') }}</span>
                    </a>
                @endif

                @endcan
            </div>
        </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
            <section class="br-panel overflow-hidden" aria-labelledby="upcoming-title">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h3 id="upcoming-title" class="font-semibold text-slate-950 dark:text-white">{{ __('dashboard_ui.upcoming_bookings') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard_ui.upcoming_description') }}</p>
                    </div>
                    @can('bookings.view')
                        <a href="{{ route('booking.management.index') }}" class="shrink-0 text-sm font-bold text-brand-indigo hover:underline">{{ __('dashboard_ui.view_all') }}</a>
                    @endcan
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($upcomingBookings as $booking)
                        @php
                            $bookingStatus = $booking->status->value;
                            $bookingPaymentStatus = $booking->payment_status->value;
                        @endphp

                        <a href="{{ route('booking.management.show', $booking) }}" class="block px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                            <div class="grid gap-4 lg:grid-cols-[auto_1fr_auto_auto] lg:items-center">
                                <div class="min-w-[96px]">
                                    <p class="text-sm font-bold text-slate-950 dark:text-white">
                                        {{ $booking->starts_at->setTimezone($timezone)->format('H:i') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $booking->starts_at->setTimezone($timezone)->format('d M') }}
                                    </p>
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $booking->customer?->name ?? '—' }}</p>
                                    <p class="mt-1 truncate text-sm text-slate-500">
                                        {{ $localized($booking->service?->name) }}
                                        <span aria-hidden="true">·</span>
                                        {{ $booking->staff?->display_name ?? __('dashboard_ui.auto_assigned') }}
                                    </p>
                                    <p class="mt-1 truncate text-xs text-slate-400">{{ $booking->booking_reference }}</p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$bookingStatus] ?? $statusClasses['pending'] }}">
                                        {{ $statusLabels[$bookingStatus] ?? $bookingStatus }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {{ $paymentClasses[$bookingPaymentStatus] ?? $paymentClasses['unpaid'] }}">
                                        {{ $paymentLabels[$bookingPaymentStatus] ?? $bookingPaymentStatus }}
                                    </span>
                                </div>

                                <div class="text-start lg:text-end">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ $money((int) $booking->service?->price_minor, (string) ($booking->service?->currency ?? 'EGP')) }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-12">
                            <div class="mx-auto max-w-md text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M4 10h16M8 14h8M8 17h5"/></svg>
                                </div>
                                <p class="mt-4 font-semibold text-slate-900 dark:text-white">{{ __('dashboard_ui.no_upcoming_bookings') }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('dashboard_ui.no_upcoming_help') }}</p>
                                <a href="{{ route('public.booking.canonical.show', ['tenant' => $tenant->slug]) }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('dashboard_ui.view_booking_page') }}
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-6">
                <section class="br-panel p-5" aria-labelledby="subscription-title">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('dashboard_ui.subscription') }}</p>
                            <h3 id="subscription-title" class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ $planName }}</h3>
                        </div>
                        @can('billing.view')
                            <a href="{{ route('billing.subscription') }}" class="text-sm font-bold text-brand-indigo hover:underline">{{ __('dashboard_ui.manage') }}</a>
                        @endcan
                    </div>

                    @if ($subscription)
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl br-surface-soft p-3">
                                <p class="text-xs font-semibold text-slate-500">{{ __('dashboard_ui.subscription_status') }}</p>
                                <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $subscriptionStatusLabels[$subscription->status->value] ?? $subscription->status->value }}</p>
                            </div>
                            <div class="rounded-xl br-surface-soft p-3">
                                <p class="text-xs font-semibold text-slate-500">{{ __('dashboard_ui.renews') }}</p>
                                <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $subscription->end_at->setTimezone($timezone)->format('d M Y') }}</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/25 dark:text-amber-200">
                            {{ __('dashboard_ui.no_active_subscription') }}
                        </p>
                    @endif
                </section>

                @can('billing.view')
                @if ($usageSummary)
                    <section class="br-panel p-5" aria-labelledby="usage-title">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('dashboard_ui.customer_usage') }}</p>
                                <h3 id="usage-title" class="mt-2 text-xl font-bold text-slate-950 dark:text-white">
                                    {{ number_format($usageSummary->uniqueCustomerCount) }} / {{ number_format($usageSummary->includedCustomerLimit) }}
                                </h3>
                            </div>
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $usagePercent }}%</span>
                        </div>

                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" aria-hidden="true">
                            <div class="h-full rounded-full bg-brand-indigo transition-all" style="width: {{ $usagePercent }}%"></div>
                        </div>

                        <p class="mt-3 text-sm text-slate-500">
                            {{ __('dashboard_ui.customers_count', ['count' => number_format($usageSummary->uniqueCustomerCount)]) }}
                        </p>

                        @if ($usageSummary->additionalCustomerCount > 0)
                            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm font-medium text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/25 dark:text-amber-200">
                                {{ __('dashboard_ui.additional_customers', ['count' => number_format($usageSummary->additionalCustomerCount)]) }}
                            </p>
                        @endif
                    </section>
                @endif
                @endcan

                <section class="br-panel p-5" aria-labelledby="workspace-title">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('dashboard_ui.workspace') }}</p>
                            <h3 id="workspace-title" class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $tenant->slug }}</h3>
                        </div>
                        <span class="rounded-full border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">
                            {{ trans_choice('dashboard_ui.active_staff_count', $metrics['activeStaff'], ['count' => $metrics['activeStaff']]) }}
                        </span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        @can('calendar.view')
                            <a href="{{ route('scheduling.index') }}" class="rounded-xl br-surface-soft px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('app.scheduling') }}
                            </a>
                        @endcan
                        @can('business.view')
                            <a href="{{ route('business.profile.edit') }}" class="rounded-xl br-surface-soft px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('app.business') }}
                            </a>
                        @endcan
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
