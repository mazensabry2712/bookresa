@extends('layouts.admin')

@section('title', __('platform.customer_intelligence').' — BookResa')
@section('heading', __('platform.customer_intelligence'))

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm text-slate-500">{{ __('platform.customer_intelligence') }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('platform.all_customers') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('platform.customer_intelligence_help') }}</p>
        @if($currencies->count() > 1 && $currency === '')
            <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                {{ __('platform.choose_currency_for_spending') }}
            </p>
        @endif
    </div>

    <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_220px_190px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="{{ __('platform.customer_search') }}"
               class="min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        <select name="tenant_id" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">{{ __('platform.all_workspaces') }}</option>
            @foreach($tenants as $tenantOption)
                <option value="{{ $tenantOption->id }}" @selected((int) request('tenant_id') === (int) $tenantOption->id)>
                    {{ data_get($tenantOption->profile?->name, app()->getLocale()) ?? $tenantOption->slug }}
                </option>
            @endforeach
        </select>
        <select name="currency" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">{{ __('platform.all_currencies') }}</option>
            @foreach($currencies as $currencyOption)
                <option value="{{ $currencyOption }}" @selected($currency === $currencyOption)>{{ $currencyOption }}</option>
            @endforeach
        </select>
        <select name="sort" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="spending_desc" @selected($sort === 'spending_desc')>{{ __('platform.sort_spending_high') }}</option>
            <option value="bookings_desc" @selected($sort === 'bookings_desc')>{{ __('platform.sort_bookings_high') }}</option>
            <option value="recent_desc" @selected($sort === 'recent_desc')>{{ __('platform.sort_recent') }}</option>
            <option value="newest_desc" @selected($sort === 'newest_desc')>{{ __('platform.sort_newest') }}</option>
            <option value="oldest_asc" @selected($sort === 'oldest_asc')>{{ __('platform.sort_oldest') }}</option>
        </select>
        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold dark:border-slate-700">
            <input type="checkbox" name="vip" value="1" @checked($vipOnly)>
            {{ __('platform.vip_only') }}
        </label>
        <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('platform.filter') }}</button>
    </form>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3 text-start">{{ __('platform.customer') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.workspace') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.spending') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.bookings') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.last_paid') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('platform.segment') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse($customers as $customerRow)
                    @php
                        $workspaceName = data_get($customerRow->tenant?->profile?->name, app()->getLocale()) ?? $customerRow->tenant?->slug ?? '—';
                        $currency = 'EGP';
                        $spent = (int) $customerRow->total_paid_minor;
                        $bookingsCount = (int) $customerRow->bookings_count;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-5 py-4">
                            <a href="{{ route('admin.customers.show', [$customerRow->tenant, $customerRow]) }}" class="font-semibold underline-offset-4 hover:underline">
                                {{ $customerRow->name }}
                            </a>
                            <p class="mt-1 text-xs text-slate-500">{{ $customerRow->phone ?: $customerRow->email ?: '—' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ $workspaceName }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $customerRow->tenant?->slug }}</p>
                        </td>
                        <td class="px-5 py-4 font-bold">
                            {{ $currency !== '' ? number_format($spent / 100, 2).' '.$currency : '—' }}
                        </td>
                        <td class="px-5 py-4">{{ number_format($bookingsCount) }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $customerRow->last_paid_at ? \Illuminate\Support\Carbon::parse($customerRow->last_paid_at)->format('Y-m-d H:i') : '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                @if($customerRow->is_vip)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">{{ __('platform.vip') }}</span>
                                @endif
                                @if($spent > 0)
                                    <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">{{ __('platform.paid_customer') }}</span>
                                @endif
                                @if($bookingsCount >= 5)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">{{ __('platform.frequent') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">{{ __('platform.no_customers') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $customers->links() }}</div>
        @endif
    </section>
</div>
@endsection
