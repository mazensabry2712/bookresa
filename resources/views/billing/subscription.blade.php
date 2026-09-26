@extends('layouts.dashboard')

@section('title', __('Subscription').' — '.config('app.name', 'BookResa'))
@section('heading', __('Subscription'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ $tenant->slug }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Plan & billing') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Manage the current workspace subscription and payment.') }}</p>
        </div>

        @if ($subscription && $subscription->status->value === 'expired')
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                <h3 class="font-semibold">{{ __('Subscription expired') }}</h3>
                <p class="mt-1">{{ __('Renew the subscription using the current plan and then continue to payment.') }}</p>
                @can('subscription.manage')
                    <form method="POST" action="{{ route('billing.subscription.renew', $subscription) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                            {{ __('Renew subscription') }}
                        </button>
                    </form>
                @endcan
            </div>
        @endif

        @if ($subscription)
            @php
                $status = $subscription->status->value;
                $paymentStatus = $subscription->payment_status->value;
                $planName = data_get($subscription->pricing_snapshot, 'name.'.app()->getLocale())
                    ?? data_get($subscription->pricing_snapshot, 'name.en')
                    ?? $subscription->plan?->name[app()->getLocale()]
                    ?? $subscription->plan?->name['en']
                    ?? '—';
            @endphp

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Plan') }}</p>
                    <p class="mt-2 text-xl font-bold">{{ $planName }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ number_format($subscription->price_minor / 100, 2) }} {{ $subscription->currency }}</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Subscription') }}</p>
                    <p class="mt-2 font-semibold">{{ str($status)->replace('_', ' ')->title() }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $subscription->start_at->format('Y-m-d') }} → {{ $subscription->end_at->format('Y-m-d') }}
                    </p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Payment') }}</p>
                    <p class="mt-2 font-semibold">{{ str($paymentStatus)->replace('_', ' ')->title() }}</p>

                    @if ($status === 'active' && $paymentStatus !== 'paid')
                        <form method="POST" action="{{ route('billing.subscription.checkout', $subscription) }}" class="mt-4">
                            @csrf
                            @can('subscription.manage')
                                <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                                    {{ __('Continue to payment') }}
                                </button>
                            @endcan
                        </form>
                    @elseif ($status === 'trial')
                        <p class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">{{ __('Trial is currently active.') }}</p>
                    @endif
                </div>
            </div>

            @if ($subscription->nextPlan)
                @php
                    $nextPlanName = data_get($subscription->nextPlan->name, app()->getLocale())
                        ?? data_get($subscription->nextPlan->name, 'en')
                        ?? '—';
                @endphp
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                    {{ __('Next plan') }}: <strong>{{ $nextPlanName }}</strong>
                    · {{ __('effective') }} {{ $subscription->plan_change_effective_at?->format('Y-m-d') }}
                </div>
            @endif

            @if ($usageSummary)
                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customers') }}</p>
                        <p class="mt-2 text-2xl font-bold">{{ number_format($usageSummary->uniqueCustomerCount) }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ number_format($usageSummary->includedCustomerLimit) }} {{ __('included') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Additional customers') }}</p>
                        <p class="mt-2 text-2xl font-bold">{{ number_format($usageSummary->additionalCustomerCount) }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ number_format($usageSummary->additionalCustomerPriceMinor / 100, 2) }} {{ $usageSummary->currency }} / {{ __('customer') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Usage fee') }}</p>
                        <p class="mt-2 text-2xl font-bold">{{ number_format($usageSummary->usageChargeMinor / 100, 2) }} {{ $usageSummary->currency }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Current period') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Estimated total') }}</p>
                        <p class="mt-2 text-2xl font-bold">{{ number_format($usageSummary->totalChargeMinor / 100, 2) }} {{ $usageSummary->currency }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Base + usage') }}</p>
                    </div>
                </div>
            @endif

            @if ($subscription && in_array($subscription->status->value, ['trial', 'active'], true))
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <h3 class="font-semibold">{{ __('Change plan') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Changes take effect at the next billing boundary.') }}</p>
                        @if ($subscription->nextPlan)
                            <p class="mt-4 text-sm font-semibold text-amber-700 dark:text-amber-300">
                                {{ __('Scheduled') }}: {{ data_get($subscription->nextPlan->name, app()->getLocale()) ?? data_get($subscription->nextPlan->name, 'en') }}
                                @if ($subscription->plan_change_effective_at)
                                    · {{ $subscription->plan_change_effective_at->format('Y-m-d') }}
                                @endif
                            </p>
                            <form method="POST" action="{{ route('billing.subscription.plan.clear', $subscription) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold dark:border-slate-700">{{ __('Clear scheduled change') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('billing.subscription.plan', $subscription) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                                @csrf
                                <select name="plan_id" required class="min-w-0 flex-1 rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <option value="">{{ __('Select a plan') }}</option>
                                    @foreach ($plans as $plan)
                                        @continue($plan->id === $subscription->plan_id)
                                        <option value="{{ $plan->id }}">{{ data_get($plan->name, app()->getLocale()) ?? data_get($plan->name, 'en') }} — {{ number_format($plan->price_minor / 100, 2) }} {{ $plan->currency }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Schedule change') }}</button>
                            </form>
                        @endif
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <h3 class="font-semibold">{{ __('Cancellation') }}</h3>
                        @if ($subscription->cancelled_at)
                            <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">{{ __('Cancellation is scheduled at') }} {{ $subscription->cancelled_at->format('Y-m-d') }}.</p>
                            <form method="POST" action="{{ route('billing.subscription.reactivate', $subscription) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Reactivate') }}</button>
                            </form>
                        @else
                            <p class="mt-2 text-sm text-slate-500">{{ __('Access remains available until the current period ends.') }}</p>
                            <form method="POST" action="{{ route('billing.subscription.cancel', $subscription) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:text-rose-300">{{ __('Cancel at period end') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            @if ($usagePeriods->isNotEmpty())
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h3 class="font-semibold">{{ __('Usage history') }}</h3></div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($usagePeriods as $period)
                            <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold">{{ $period->period_start->format('Y-m-d') }} → {{ $period->period_end->format('Y-m-d') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ number_format($period->unique_customer_count) }} {{ __('customers') }} · {{ number_format($period->additional_customer_count) }} {{ __('additional') }}</p>
                                </div>
                                <div class="text-sm font-semibold">{{ number_format($period->total_charge_minor / 100, 2) }} {{ $period->currency }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold">{{ __('Payment history') }}</h3>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($payments as $payment)
                        <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-mono text-sm font-semibold">{{ $payment->reference }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $payment->provider }} · {{ $payment->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <div class="text-sm font-semibold">{{ str($payment->status->value)->replace('_', ' ')->title() }}</div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-slate-500">{{ __('No subscription payments yet.') }}</p>
                    @endforelse
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <h3 class="text-lg font-semibold">{{ __('No subscription found') }}</h3>
                <p class="mt-2 text-sm text-slate-500">{{ __('A subscription will appear here after a plan is selected.') }}</p>
            </div>
        @endif
    </div>
@endsection
