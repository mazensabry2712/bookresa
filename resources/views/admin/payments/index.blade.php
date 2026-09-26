@extends('layouts.admin')

@section('title', __('Payments').' — BookResa')
@section('heading', __('Payments'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform finance') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Payments') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Review gateway activity and payment status across all workspaces.') }}</p>
        </div>

        <form method="GET" class="grid gap-3 sm:grid-cols-3">
            <select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Domain\Payment\Enums\PaymentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->headline() }}</option>
                @endforeach
            </select>
            <select name="provider" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('All providers') }}</option>
                @foreach ($providers as $provider)
                    <option value="{{ $provider }}" @selected(request('provider') === $provider)>{{ $provider }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Filter') }}</button>
        </form>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('Business') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Reference') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Provider') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Amount') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Paid at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-5 py-4">{{ data_get($payment->tenant?->profile?->name, app()->getLocale()) ?? $payment->tenant?->slug ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ $payment->reference }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $payment->provider_reference ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ $payment->provider }}</td>
                                <td class="px-5 py-4 font-semibold">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800">{{ str($payment->status->value)->headline() }}</span></td>
                                <td class="px-5 py-4 text-slate-500">{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No payments found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($payments->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $payments->links() }}</div>
            @endif
        </section>
    </div>
@endsection
