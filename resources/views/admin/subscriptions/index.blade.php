@extends('layouts.admin')

@section('title', __('Subscriptions').' — BookResa')
@section('heading', __('Subscriptions'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform billing') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Subscriptions') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Review subscription status, pricing snapshots and billing periods across workspaces.') }}</p>
        </div>

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Domain\Billing\Enums\SubscriptionStatus::cases() as $status)
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
                            <th class="px-5 py-3 text-start">{{ __('Plan') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Price') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Period') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Ends') }}</th>
                            <th class="px-5 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($subscriptions as $subscription)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ data_get($subscription->tenant?->profile?->name, app()->getLocale()) ?? $subscription->tenant?->slug ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $subscription->tenant?->slug }}</p>
                                </td>
                                <td class="px-5 py-4">{{ data_get($subscription->plan?->name, app()->getLocale()) ?? $subscription->plan?->slug ?? '—' }}</td>
                                <td class="px-5 py-4 font-semibold">{{ number_format($subscription->price_minor / 100, 2) }} {{ $subscription->currency }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ str($subscription->billing_period->value)->replace('_', ' ')->title() }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ str($subscription->status->value)->headline() }}</span>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ $subscription->end_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-5 py-4 text-end">
                                    @if (in_array($subscription->status, [
                                        \App\Domain\Billing\Enums\SubscriptionStatus::Trial,
                                        \App\Domain\Billing\Enums\SubscriptionStatus::Active,
                                        \App\Domain\Billing\Enums\SubscriptionStatus::Suspended,
                                    ], true))
                                        <form method="POST" action="{{ route('admin.subscriptions.toggle-status', $subscription) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-700">
                                                {{ $subscription->status === \App\Domain\Billing\Enums\SubscriptionStatus::Suspended ? __('Activate') : __('Suspend') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No subscriptions found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($subscriptions->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $subscriptions->links() }}</div>
            @endif
        </section>
    </div>
@endsection
