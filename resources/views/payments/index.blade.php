@extends('layouts.dashboard')

@section('title', __('app.payment_ui.payments').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.payment_ui.payments'))

@section('content')
    @php
        $statusLabels = [
            'pending' => __('app.payment_ui.status_pending'),
            'paid' => __('app.payment_ui.status_paid'),
            'failed' => __('app.payment_ui.status_failed'),
            'refunded' => __('app.payment_ui.status_refunded'),
            'expired' => __('app.payment_ui.status_expired'),
        ];

        $statusClasses = [
            'pending' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
            'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-300',
            'failed' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-300',
            'refunded' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
            'expired' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ];
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.payment_ui.customer_payments') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.payment_ui.page_help') }}</p>
            </div>

            @can('subscription.manage')
                <a href="{{ route('billing.subscription') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('app.payment_ui.manage_subscription') }}
                </a>
            @endcan
        </section>

        <section class="grid gap-3 sm:grid-cols-2">
            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.payment_ui.payment_records') }}</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ number_format($payments->total()) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.payment_ui.payment_records_help') }}</p>
            </article>

            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.payment_ui.status') }}</p>
                <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">
                    {{ $status !== '' && isset($statusLabels[$status]) ? $statusLabels[$status] : __('app.payment_ui.all_statuses') }}
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.payment_ui.status_help') }}</p>
            </article>
        </section>

        <section class="br-panel p-4 sm:p-5">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="w-full sm:max-w-xs">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.payment_ui.status') }}</span>
                    <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('app.payment_ui.all_statuses') }}</option>
                        @foreach ($statuses as $paymentStatus)
                            <option value="{{ $paymentStatus->value }}" @selected($status === $paymentStatus->value)>
                                {{ $statusLabels[$paymentStatus->value] ?? str($paymentStatus->value)->headline() }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">
                        {{ __('app.payment_ui.apply_filter') }}
                    </button>
                    <a href="{{ route('payments.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.payment_ui.reset') }}
                    </a>
                </div>
            </form>
        </section>

        <section class="br-panel overflow-hidden">
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/60">
                        <tr class="text-start text-xs font-bold uppercase tracking-[0.12em] text-slate-400">
                            <th class="px-5 py-4">{{ __('app.payment_ui.reference') }}</th>
                            <th class="px-5 py-4">{{ __('app.payment_ui.customer') }}</th>
                            <th class="px-5 py-4">{{ __('app.payment_ui.booking') }}</th>
                            <th class="px-5 py-4">{{ __('app.payment_ui.amount') }}</th>
                            <th class="px-5 py-4">{{ __('app.payment_ui.status') }}</th>
                            <th class="px-5 py-4">{{ __('app.payment_ui.provider') }}</th>
                            <th class="px-5 py-4">{{ __('app.payment_ui.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($payments as $payment)
                            @php $booking = $payment->payable; @endphp
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-950/30">
                                <td class="px-5 py-4 font-mono text-xs font-bold text-slate-700 dark:text-slate-200">{{ $payment->reference }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950 dark:text-white">{{ $booking?->customer?->name ?? '—' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $booking?->customer?->phone ?? $booking?->customer?->email ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($booking)
                                        <a href="{{ route('booking.management.show', $booking) }}" class="font-bold text-brand-indigo hover:underline">{{ $booking->booking_reference }}</a>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $booking?->service?->name[app()->getLocale()] ?? $booking?->service?->name['en'] ?? '—' }}</p>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-950 dark:text-white">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$payment->status->value] ?? $statusClasses['pending'] }}">
                                        {{ $statusLabels[$payment->status->value] ?? $payment->status->value }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ str($payment->provider)->headline() }}</p>
                                    @if ($payment->provider_reference)
                                        <p class="mt-0.5 max-w-40 truncate font-mono text-xs text-slate-400">{{ $payment->provider_reference }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                    {{ $payment->paid_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') ?? $payment->created_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <p class="font-bold text-slate-950 dark:text-white">{{ __('app.payment_ui.no_payments') }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ __('app.payment_ui.no_payments_help') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-200 md:hidden dark:divide-slate-800">
                @forelse ($payments as $payment)
                    @php $booking = $payment->payable; @endphp
                    <a href="{{ $booking ? route('booking.management.show', $booking) : '#' }}" class="block p-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/30">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-mono text-xs font-bold text-slate-400">{{ $payment->reference }}</p>
                                <p class="mt-1 font-bold text-slate-950 dark:text-white">{{ $booking?->customer?->name ?? '—' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $booking?->booking_reference ?? __('app.payment_ui.booking_unavailable') }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$payment->status->value] ?? $statusClasses['pending'] }}">
                                {{ $statusLabels[$payment->status->value] ?? $payment->status->value }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-4">
                            <span class="text-sm font-extrabold text-slate-950 dark:text-white">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</span>
                            <span class="text-xs text-slate-500">{{ $payment->paid_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') ?? $payment->created_at?->setTimezone(data_get($tenant->profile, 'timezone', 'UTC'))->format('d M Y, H:i') }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-14 text-center">
                        <p class="font-bold text-slate-950 dark:text-white">{{ __('app.payment_ui.no_payments') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('app.payment_ui.no_payments_help') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($payments->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $payments->links() }}</div>
            @endif
        </section>
    </div>
@endsection
