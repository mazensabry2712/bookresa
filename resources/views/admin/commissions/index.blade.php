@extends('layouts.admin')

@section('title', __('Booking commissions & settlements').' — BookResa')
@section('heading', __('Booking commissions & settlements'))

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Platform commission total') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ number_format($totalCommissionMinor / 100, 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Totals reflect paid booking transactions and refunds; amounts are grouped by currency below.') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Paid out') }}</p>
                <p class="mt-2 text-2xl font-bold">{{ number_format($totalPaidOutMinor / 100, 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Amounts are recorded only after an external transfer reference is entered.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div>
                <h2 class="font-semibold">{{ __('Workspace balances') }}</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('Workspace net and available balances are calculated after BookResa commission and refunds, but before provider processing and payout fees.') }}</p>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[760px] text-start text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="py-3 pe-4 font-semibold">{{ __('Workspace') }}</th>
                            <th class="py-3 pe-4 font-semibold">{{ __('Currency') }}</th>
                            <th class="py-3 pe-4 font-semibold">{{ __('Commission earned') }}</th>
                            <th class="py-3 pe-4 font-semibold">{{ __('Workspace net') }}</th>
                            <th class="py-3 pe-4 font-semibold">{{ __('Reserved') }}</th>
                            <th class="py-3 font-semibold">{{ __('Available to pay out') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($balances as $balance)
                            <tr>
                                <td class="py-3 pe-4">
                                    <p class="font-semibold">{{ $balance['tenant_name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $balance['slug'] }}</p>
                                </td>
                                <td class="py-3 pe-4">{{ $balance['currency'] }}</td>
                                <td class="py-3 pe-4">{{ number_format($balance['commission_minor'] / 100, 2) }}</td>
                                <td class="py-3 pe-4">{{ number_format($balance['workspace_net_minor'] / 100, 2) }}</td>
                                <td class="py-3 pe-4">{{ number_format($balance['reserved_minor'] / 100, 2) }}</td>
                                <td class="py-3 font-semibold">{{ number_format($balance['available_minor'] / 100, 2) }}</td>
                            </tr>
                            @if ($balance['available_minor'] > 0)
                                <tr>
                                    <td colspan="6" class="pb-4">
                                        <form method="POST" action="{{ route('admin.commissions.payouts.store') }}" class="grid gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60 sm:grid-cols-6">
                                            @csrf
                                            <input type="hidden" name="tenant_id" value="{{ $balance['tenant_id'] }}">
                                            <input type="hidden" name="currency" value="{{ $balance['currency'] }}">
                                            <label class="text-xs sm:col-span-1">
                                                <span class="mb-1 block font-semibold">{{ __('Amount to reserve') }}</span>
                                                <input name="amount" type="number" min="0.01" max="{{ number_format($balance['available_minor'] / 100, 2, '.', '') }}" step="0.01" required class="w-full rounded-lg border-slate-300 px-2.5 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                            </label>
                                            <label class="text-xs sm:col-span-2">
                                                <span class="mb-1 block font-semibold">{{ __('Payout method / destination') }}</span>
                                                <input name="payout_method" maxlength="80" class="w-full rounded-lg border-slate-300 px-2.5 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                            </label>
                                            <label class="text-xs sm:col-span-2">
                                                <span class="mb-1 block font-semibold">{{ __('Notes') }}</span>
                                                <input name="notes" maxlength="2000" class="w-full rounded-lg border-slate-300 px-2.5 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                            </label>
                                            <button class="self-end rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white dark:bg-white dark:text-slate-900">{{ __('Reserve payout') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-slate-500">{{ __('No commission records yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h2 class="font-semibold">{{ __('Workspace payouts') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('This page records payouts; it does not transfer money. Execute the transfer through your approved provider or bank, then record its reference here.') }}</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[740px] text-start text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="py-3 pe-3 font-semibold">{{ __('Workspace') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Payout reference') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Amount') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Payout method / destination') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Status') }}</th>
                            <th class="py-3 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($payouts as $payout)
                            <tr>
                                <td class="py-3 pe-3">{{ data_get($payout->tenant?->profile?->name, app()->getLocale()) ?? $payout->tenant?->slug ?? $payout->tenant_id }}</td>
                                <td class="py-3 pe-3">
                                    <p class="font-mono text-xs">{{ $payout->reference }}</p>
                                    @if ($payout->payout_reference)
                                        <p class="mt-1 break-all text-xs text-slate-500">{{ $payout->payout_reference }}</p>
                                    @endif
                                </td>
                                <td class="py-3 pe-3 font-semibold">{{ number_format($payout->amount_minor / 100, 2) }} {{ $payout->currency }}</td>
                                <td class="py-3 pe-3">{{ $payout->payout_method ?: '—' }}</td>
                                <td class="py-3 pe-3">{{ __($payout->status === 'pending' ? 'Pending' : ($payout->status === 'paid' ? 'Paid' : 'Cancelled')) }}</td>
                                <td class="py-3">
                                    @if ($payout->status === 'pending')
                                        <div class="flex min-w-56 flex-col gap-2">
                                            <form method="POST" action="{{ route('admin.commissions.payouts.paid', $payout) }}" class="flex gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input name="payout_reference" required maxlength="128" placeholder="{{ __('Payout reference') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-950">
                                                <button class="rounded-lg bg-emerald-700 px-2.5 py-1.5 text-xs font-bold text-white">{{ __('Mark as paid') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.commissions.payouts.cancel', $payout) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold dark:border-slate-700">{{ __('Cancel reservation') }}</button>
                                            </form>
                                        </div>
                                    @elseif ($payout->paid_at)
                                        <time class="text-xs text-slate-500">{{ $payout->paid_at->format('Y-m-d H:i') }}</time>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-slate-500">{{ __('No payouts yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $payouts->links() }}</div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h2 class="font-semibold">{{ __('Centralized commission ledger') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Online payment entries are reconciled to the confirmed booking’s monthly allocation. Cash commission receivables are not yet collected or reconciled by this ledger.') }}</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[760px] text-start text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="py-3 pe-3 font-semibold">{{ __('Workspace') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Payment reference') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Booking number in month') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Free booking allowance') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Gross paid') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Refunded') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Commission') }}</th>
                            <th class="py-3 pe-3 font-semibold">{{ __('Workspace net') }}</th>
                            <th class="py-3 font-semibold">{{ __('Eligibility') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($commissions as $commission)
                            <tr>
                                <td class="py-3 pe-3">{{ data_get($commission->tenant?->profile?->name, app()->getLocale()) ?? $commission->tenant?->slug ?? $commission->tenant_id }}</td>
                                <td class="py-3 pe-3 font-mono text-xs">{{ $commission->payment?->reference ?? '—' }}</td>
                                <td class="py-3 pe-3">{{ number_format($commission->booking_ordinal ?? $commission->customer_ordinal) }}</td>
                                <td class="py-3 pe-3">{{ number_format($commission->free_booking_allowance ?? $commission->included_customer_limit) }}</td>
                                <td class="py-3 pe-3">{{ number_format($commission->gross_amount_minor / 100, 2) }} {{ $commission->currency }}</td>
                                <td class="py-3 pe-3">{{ number_format($commission->refunded_amount_minor / 100, 2) }}</td>
                                <td class="py-3 pe-3">{{ number_format($commission->commission_amount_minor / 100, 2) }}</td>
                                <td class="py-3 pe-3">{{ number_format($commission->workspace_net_minor / 100, 2) }}</td>
                                <td class="py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold dark:bg-slate-800">{{ $commission->eligible ? __('Commission eligible') : __('No commission') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-8 text-center text-slate-500">{{ __('No commission records yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $commissions->links() }}</div>
        </section>
    </div>
@endsection
