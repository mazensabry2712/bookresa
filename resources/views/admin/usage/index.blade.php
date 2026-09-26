@extends('layouts.admin')

@section('title', __('Usage').' — BookResa')
@section('heading', __('Usage'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Customer usage billing') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Usage') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Review immutable customer-usage periods and calculated charges.') }}</p>
        </div>

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Domain\Billing\Enums\UsagePeriodStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->headline() }}</option>
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
                            <th class="px-5 py-3 text-start">{{ __('Period') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Customers') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Included') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Additional') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Charge') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($usagePeriods as $usage)
                            <tr>
                                <td class="px-5 py-4">{{ data_get($usage->tenant?->profile?->name, app()->getLocale()) ?? $usage->tenant?->slug ?? '—' }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $usage->period_start?->format('Y-m-d') }} — {{ $usage->period_end?->format('Y-m-d') }}</td>
                                <td class="px-5 py-4">{{ number_format($usage->unique_customer_count) }}</td>
                                <td class="px-5 py-4">{{ number_format($usage->included_customer_limit) }}</td>
                                <td class="px-5 py-4">{{ number_format($usage->additional_customer_count) }}</td>
                                <td class="px-5 py-4 font-semibold">{{ number_format($usage->usage_charge_minor / 100, 2) }} {{ $usage->currency }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800">{{ str($usage->status->value)->headline() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No usage periods found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($usagePeriods->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $usagePeriods->links() }}</div>
            @endif
        </section>
    </div>
@endsection
