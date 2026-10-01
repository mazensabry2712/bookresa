@extends('layouts.admin')

@section('title', $customer->name.' — '.__('platform.customer_intelligence'))
@section('heading', $customer->name)

@section('content')
<div class="space-y-6">
    @php
        $workspaceName = data_get($tenant->profile?->name, app()->getLocale()) ?? $tenant->slug;
        $currencyTotals = $metrics['currencyTotals'] ?? [];
        $singleCurrency = count($currencyTotals) === 1 ? (string) array_key_first($currencyTotals) : null;
        $money = static fn (int $minor, ?string $currency = null): string => number_format($minor / 100, 2).' '.($currency ?? '');
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">{{ $workspaceName }} · {{ __('platform.customer_intelligence') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ $customer->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $customer->phone ?: $customer->email ?: __('platform.no_contact_data') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.customers.index', ['tenant_id' => $tenant->id]) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('platform.back_to_customer_data') }}</a>
            <a href="{{ route('admin.businesses.show', $tenant) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('platform.workspace_details') }}</a>
            <form method="POST" action="{{ route('admin.customers.vip-toggle', [$tenant, $customer]) }}">
                @csrf
                @method('PATCH')
                <button class="rounded-xl px-4 py-2.5 text-sm font-bold {{ $customer->is_vip ? 'bg-amber-500 text-white' : 'border border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300' }}">
                    {{ $customer->is_vip ? __('platform.remove_vip') : __('platform.mark_vip') }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [__('platform.total_spending'), $singleCurrency !== null
                ? $money((int) $metrics['totalSpentMinor'], $singleCurrency)
                : __('platform.multiple_currencies')],
            [__('platform.bookings'), number_format($metrics['bookings'])],
            [__('platform.completed'), number_format($metrics['completedBookings'])],
            [__('platform.average_paid'), $singleCurrency !== null
                ? $money((int) $metrics['averagePaidMinor'], $singleCurrency)
                : '—'],
        ] as [$label,$value])
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    @if(count($currencyTotals) > 0)
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div>
                <h3 class="font-semibold">{{ __('platform.spending_by_currency') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('platform.spending_by_currency_help') }}</p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($currencyTotals as $currency => $minor)
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $currency }}</p>
                        <p class="mt-1 text-xl font-bold">{{ $money($minor, $currency) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h3 class="font-semibold">{{ __('platform.contact_details') }}</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('platform.email') }}</dt><dd class="font-semibold text-end">{{ $customer->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('platform.phone') }}</dt><dd class="font-semibold text-end">{{ $customer->phone ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('platform.first_seen') }}</dt><dd class="font-semibold text-end">{{ $customer->first_seen_at?->format('Y-m-d H:i') ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('platform.last_seen') }}</dt><dd class="font-semibold text-end">{{ $customer->last_seen_at?->format('Y-m-d H:i') ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('platform.last_paid') }}</dt><dd class="font-semibold text-end">{{ $metrics['lastPaidAt'] ? \Illuminate\Support\Carbon::parse($metrics['lastPaidAt'])->format('Y-m-d H:i') : '—' }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 lg:col-span-2">
            <h3 class="font-semibold">{{ __('platform.customer_signals') }}</h3>
            <div class="mt-4 flex flex-wrap gap-2">
                @if($customer->is_vip)
                    <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">{{ __('platform.vip') }}</span>
                @endif
                @if($metrics['totalSpentMinor'] > 0)
                    <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">{{ __('platform.paid_customer') }}</span>
                @endif
                @if($metrics['bookings'] >= 5)
                    <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">{{ __('platform.frequent') }}</span>
                @endif
                @if($metrics['completedBookings'] >= 3)
                    <span class="rounded-full bg-sky-100 px-3 py-1.5 text-xs font-bold text-sky-800 dark:bg-sky-950/40 dark:text-sky-200">{{ __('platform.repeat_customer') }}</span>
                @endif
            </div>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h3 class="font-semibold">{{ __('platform.booking_history') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3 text-start">{{ __('platform.reference') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.service') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.date') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.status') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.payment_status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse($bookings as $booking)
                    <tr>
                        <td class="px-5 py-4 font-semibold">{{ $booking->booking_reference }}</td>
                        <td class="px-5 py-4">{{ data_get($booking->service?->name, app()->getLocale()) ?? data_get($booking->service?->name, 'en') ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $booking->starts_at->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-4">{{ str($booking->status->value)->headline() }}</td>
                        <td class="px-5 py-4">{{ str($booking->payment_status->value)->headline() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('platform.no_booking_history') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h3 class="font-semibold">{{ __('platform.payment_history') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3 text-start">{{ __('platform.reference') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.amount') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.provider') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.status') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse($payments as $payment)
                    <tr>
                        <td class="px-5 py-4 font-semibold">{{ $payment->reference }}</td>
                        <td class="px-5 py-4 font-bold">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</td>
                        <td class="px-5 py-4">{{ $payment->provider }}</td>
                        <td class="px-5 py-4">{{ str($payment->status->value)->headline() }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('platform.no_payment_history') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
