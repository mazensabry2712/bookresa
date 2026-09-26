@extends('layouts.dashboard')

@section('title', __('app.billing_ui.subscription').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.billing_ui.subscription'))

@section('content')
    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $statusLabels = [
            'trial' => __('app.billing_ui.trial'),
            'active' => __('app.billing_ui.active'),
            'expired' => __('app.billing_ui.expired'),
            'suspended' => __('app.billing_ui.suspended'),
            'cancelled' => __('app.billing_ui.cancelled'),
        ];

        $paymentLabels = [
            'pending' => __('app.billing_ui.payment_pending'),
            'paid' => __('app.billing_ui.payment_paid'),
            'failed' => __('app.billing_ui.payment_failed'),
            'refunded' => __('app.billing_ui.payment_refunded'),
            'expired' => __('app.billing_ui.payment_expired'),
        ];

        $statusClasses = [
            'trial' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-300',
            'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300',
            'expired' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
            'suspended' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300',
            'cancelled' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ];

        $usagePercent = $usageSummary && $usageSummary->includedCustomerLimit > 0
            ? min(100, (int) round(($usageSummary->uniqueCustomerCount / $usageSummary->includedCustomerLimit) * 100))
            : 0;
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.billing_ui.title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.billing_ui.page_help') }}</p>
            </div>
        </section>

        @if ($subscription)
            @php
                $planName = data_get($subscription->pricing_snapshot, 'name.'.app()->getLocale())
                    ?? data_get($subscription->pricing_snapshot, 'name.en')
                    ?? $localized($subscription->plan?->name);
                $status = $subscription->status->value;
                $paymentStatus = $subscription->payment_status->value;
            @endphp

            @if ($status === 'expired')
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900/70 dark:bg-amber-950/30">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">{{ __('app.billing_ui.subscription_expired') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-amber-950 dark:text-amber-100">{{ __('app.billing_ui.renew_title') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-amber-800 dark:text-amber-200">{{ __('app.billing_ui.renew_help') }}</p>
                    @can('subscription.manage')
                        <form method="POST" action="{{ route('billing.subscription.renew', $subscription) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">
                                {{ __('app.billing_ui.renew') }}
                            </button>
                        </form>
                    @endcan
                </section>
            @endif

            <section class="br-panel p-5 sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.billing_ui.current_plan') }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <h3 class="text-2xl font-extrabold text-slate-950 dark:text-white">{{ $planName }}</h3>
                            <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$status] ?? $statusClasses['cancelled'] }}">
                                {{ $statusLabels[$status] ?? str($status)->headline() }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ number_format($subscription->price_minor / 100, 2) }} {{ $subscription->currency }}
                            · {{ $subscription->start_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y') }}
                            — {{ $subscription->end_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y') }}
                        </p>
                    </div>

                    <div class="rounded-2xl br-surface-soft p-4 lg:min-w-72">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.billing_ui.subscription_payment') }}</p>
                        <p class="mt-2 font-bold text-slate-950 dark:text-white">{{ $paymentLabels[$paymentStatus] ?? str($paymentStatus)->headline() }}</p>
                        @can('subscription.manage')
                            @if (in_array($status, ['active', 'trial'], true) && $paymentStatus !== 'paid')
                                <form method="POST" action="{{ route('billing.subscription.checkout', $subscription) }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                                        {{ __('app.billing_ui.continue_payment') }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>

                @if ($subscription->cancelled_at)
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/25 dark:text-amber-200">
                        {{ __('app.billing_ui.cancellation_scheduled', ['date' => $subscription->cancelled_at->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y')]) }}
                    </div>
                @endif

                @if ($subscription->nextPlan)
                    <div class="mt-3 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800 dark:border-indigo-900/70 dark:bg-indigo-950/25 dark:text-indigo-200">
                        {{ __('app.billing_ui.next_plan', [
                            'plan' => $localized($subscription->nextPlan->name),
                            'date' => $subscription->plan_change_effective_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y'),
                        ]) }}
                    </div>
                @endif
            </section>

            @if ($usageSummary)
                <section class="br-panel p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.billing_ui.usage') }}</p>
                            <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ number_format($usageSummary->uniqueCustomerCount) }} / {{ number_format($usageSummary->includedCustomerLimit) }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.billing_ui.usage_help') }}</p>
                        </div>
                        <p class="text-2xl font-extrabold text-slate-950 dark:text-white">{{ $usagePercent }}%</p>
                    </div>

                    <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-brand-indigo" style="width: {{ $usagePercent }}%"></div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            [__('app.billing_ui.customers'), number_format($usageSummary->uniqueCustomerCount)],
                            [__('app.billing_ui.included'), number_format($usageSummary->includedCustomerLimit)],
                            [__('app.billing_ui.additional'), number_format($usageSummary->additionalCustomerCount)],
                            [__('app.billing_ui.usage_charge'), number_format($usageSummary->usageChargeMinor / 100, 2).' '.$usageSummary->currency],
                            [__('app.billing_ui.estimated_total'), number_format($usageSummary->totalChargeMinor / 100, 2).' '.$usageSummary->currency],
                        ] as [$label, $value])
                            <div class="rounded-xl br-surface-soft p-4">
                                <p class="text-xs font-semibold text-slate-400">{{ $label }}</p>
                                <p class="mt-1.5 text-lg font-extrabold text-slate-950 dark:text-white">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (in_array($status, ['trial', 'active'], true))
                <div class="grid gap-5 lg:grid-cols-2">
                    <section class="br-panel p-5 sm:p-6">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.billing_ui.plan_change') }}</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.billing_ui.plan_change_title') }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.billing_ui.plan_change_help') }}</p>
                        </div>

                        @can('subscription.manage')
                            @if ($subscription->nextPlan)
                                <p class="mt-4 rounded-xl br-surface-soft px-4 py-3 text-sm font-semibold">
                                    {{ __('app.billing_ui.scheduled_plan', ['plan' => $localized($subscription->nextPlan->name)]) }}
                                </p>
                                <form method="POST" action="{{ route('billing.subscription.plan.clear', $subscription) }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.billing_ui.clear_change') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('billing.subscription.plan', $subscription) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                                    @csrf
                                    <select name="plan_id" required class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        <option value="">{{ __('app.billing_ui.select_plan') }}</option>
                                        @foreach ($plans as $plan)
                                            @continue($plan->id === $subscription->plan_id)
                                            <option value="{{ $plan->id }}">{{ $localized($plan->name) }} — {{ number_format($plan->price_minor / 100, 2) }} {{ $plan->currency }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">{{ __('app.billing_ui.schedule_change') }}</button>
                                </form>
                            @endif
                        @endcan
                    </section>

                    <section class="br-panel p-5 sm:p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.billing_ui.cancellation') }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.billing_ui.cancellation_title') }}</h3>

                        @can('subscription.manage')
                            @if ($subscription->cancelled_at)
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('app.billing_ui.cancellation_restore_help') }}</p>
                                <form method="POST" action="{{ route('billing.subscription.reactivate', $subscription) }}" class="mt-4">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">{{ __('app.billing_ui.reactivate') }}</button>
                                </form>
                            @else
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('app.billing_ui.cancellation_help') }}</p>
                                <form method="POST" action="{{ route('billing.subscription.cancel', $subscription) }}" class="mt-4" onsubmit="return confirm(@json(__('app.billing_ui.cancel_confirm')))">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30">{{ __('app.billing_ui.cancel_at_period_end') }}</button>
                                </form>
                            @endif
                        @endcan
                    </section>
                </div>
            @endif

            @if ($usagePeriods->isNotEmpty())
                <section class="br-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.billing_ui.usage_history') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($usagePeriods as $period)
                            <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold">{{ $period->period_start->format('d M Y') }} — {{ $period->period_end->format('d M Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ number_format($period->unique_customer_count) }} {{ __('app.billing_ui.customers') }} · {{ number_format($period->additional_customer_count) }} {{ __('app.billing_ui.additional') }}</p>
                                </div>
                                <p class="font-bold">{{ number_format($period->total_charge_minor / 100, 2) }} {{ $period->currency }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="br-panel overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.billing_ui.payment_history') }}</h3>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($payments as $payment)
                        <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-mono text-sm font-bold">{{ $payment->reference }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ str($payment->provider)->headline() }} · {{ $payment->created_at->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') }}</p>
                            </div>
                            <div class="text-start sm:text-end">
                                <p class="font-bold">{{ $paymentLabels[$payment->status->value] ?? $payment->status->value }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500">{{ __('app.billing_ui.no_subscription_payments') }}</div>
                    @endforelse
                </div>
            </section>
        @else
            <section class="br-panel p-5 sm:p-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.billing_ui.choose_plan') }}</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ __('app.billing_ui.choose_plan_title') }}</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.billing_ui.choose_plan_help') }}</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($plans as $plan)
                        <article class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                            <h4 class="text-lg font-bold text-slate-950 dark:text-white">{{ $localized($plan->name) }}</h4>
                            <p class="mt-3 text-2xl font-extrabold text-slate-950 dark:text-white">{{ number_format($plan->price_minor / 100, 2) }} {{ $plan->currency }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ str($plan->billing_period->value)->replace('_', ' ')->title() }}</p>
                            @if ($plan->trial_days > 0)
                                <p class="mt-3 text-sm font-bold text-emerald-600 dark:text-emerald-300">{{ $plan->trial_days }} {{ __('app.billing_ui.trial_days') }}</p>
                            @endif
                            @can('subscription.manage')
                                <form method="POST" action="{{ route('billing.subscribe', $plan) }}" class="mt-5">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.billing_ui.select_plan') }}</button>
                                </form>
                            @endcan
                        </article>
                    @empty
                        <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">{{ __('app.billing_ui.no_plans') }}</div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
@endsection
