@extends('layouts.dashboard')

@section('title', __('Dashboard').' — '.config('bookresa.name', 'Velto'))
@section('heading', __('Dashboard'))

@section('content')
    @php
        $money = static fn (int $minor, string $currency = 'EGP'): string => number_format($minor / 100, 2).' '.$currency;
        $planName = data_get($subscription?->plan?->name, app()->getLocale())
            ?? data_get($subscription?->plan?->name, 'en')
            ?? '—';
    @endphp

    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Business workspace') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">
                {{ data_get($tenant->profile?->name, app()->getLocale()) ?? data_get($tenant->profile?->name, 'en') ?? $tenant->slug }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Run today’s bookings, customers, staff and subscription from one place.') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Today’s bookings') }}</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($metrics['todayBookings']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Today’s revenue') }}</p>
                <p class="mt-2 text-3xl font-bold">{{ $money($metrics['todayRevenueMinor']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customers') }}</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($metrics['customers']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Employees') }}</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($metrics['employees']) }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h3 class="font-semibold">{{ __('Upcoming bookings') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Your next appointments in order.') }}</p>
                    </div>
                    <a href="{{ route('booking.management.index') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('View all') }}</a>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($upcomingBookings as $booking)
                        <a href="{{ route('booking.management.show', $booking) }}" class="block px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-950/40">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold">{{ $booking->customer?->name ?? '—' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ data_get($booking->service?->name, app()->getLocale()) ?? data_get($booking->service?->name, 'en') ?? '—' }}
                                        · {{ $booking->staff?->display_name ?? __('Auto assigned') }}
                                    </p>
                                </div>
                                <div class="text-start sm:text-end">
                                    <p class="font-semibold">{{ $booking->starts_at->setTimezone($timezone)->format('d M, H:i') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ str($booking->status->value)->replace('_', ' ')->title() }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <p class="font-semibold">{{ __('No upcoming bookings') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ __('New customer bookings will appear here.') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Subscription') }}</p>
                            <p class="mt-2 text-xl font-bold">{{ $planName }}</p>
                        </div>
                        <a href="{{ route('billing.subscription') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('Manage') }}</a>
                    </div>
                    @if ($subscription)
                        <p class="mt-2 text-sm text-slate-500">
                            {{ str($subscription->status->value)->headline() }}
                            · {{ $subscription->end_at->format('d M Y') }}
                        </p>
                    @else
                        <p class="mt-2 text-sm text-slate-500">{{ __('No active subscription.') }}</p>
                    @endif
                </div>

                @if ($usageSummary)
                    @php
                        $limit = max((int) $usageSummary->includedCustomerLimit, 1);
                        $usagePercent = min(100, (int) round(($usageSummary->uniqueCustomerCount / $limit) * 100));
                    @endphp
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer usage') }}</p>
                                <p class="mt-2 text-xl font-bold">{{ number_format($usageSummary->uniqueCustomerCount) }} / {{ number_format($usageSummary->includedCustomerLimit) }}</p>
                            </div>
                            <span class="text-sm font-semibold">{{ $usagePercent }}%</span>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-slate-900 dark:bg-white" style="width: {{ $usagePercent }}%"></div>
                        </div>
                        @if ($usageSummary->additionalCustomerCount > 0)
                            <p class="mt-3 text-sm text-amber-700 dark:text-amber-300">
                                {{ number_format($usageSummary->additionalCustomerCount) }} {{ __('additional customers are currently billed according to your plan.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
