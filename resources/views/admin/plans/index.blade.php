@extends('layouts.admin')

@section('title', __('Plans').' — BookResa')
@section('heading', __('Plans'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform billing configuration') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Subscription plans') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Control pricing, customer limits, trial periods and enabled modules.') }}</p>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Create plan') }}</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Plans') }}</p>
            <p class="mt-2 text-2xl font-bold">{{ $plans->total() }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Active plans') }}</p>
            <p class="mt-2 text-2xl font-bold">{{ $activePlanCount }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                <tr>
                    <th class="px-5 py-3 text-start">{{ __('Plan') }}</th>
                    <th class="px-5 py-3 text-start">{{ __('Price') }}</th>
                    <th class="px-5 py-3 text-start">{{ __('Customers') }}</th>
                    <th class="px-5 py-3 text-start">{{ __('Modules') }}</th>
                    <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                    <th class="px-5 py-3 text-start">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($plans as $plan)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ data_get($plan->name, app()->getLocale()) ?? data_get($plan->name, 'en') }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $plan->active_subscriptions_count }} {{ __('active/trial subscriptions') }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ number_format($plan->price_minor / 100, 2) }} {{ $plan->currency }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ str($plan->billing_period->value)->replace('_', ' ')->title() }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p>{{ number_format($plan->included_customer_limit) }} {{ __('included') }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ number_format($plan->additional_customer_price_minor / 100, 2) }} {{ $plan->currency }} / {{ __('extra') }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p>{{ $plan->modules->count() }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $plan->trial_days }} {{ __('trial days') }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $plan->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $plan->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-700">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('admin.plans.toggle', $plan) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-700">{{ $plan->is_active ? __('Deactivate') : __('Activate') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No plans created yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($plans->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $plans->links() }}</div>
        @endif
    </div>
</div>
@endsection
