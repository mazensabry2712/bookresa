@extends('layouts.dashboard')

@section('title', __('app.customer_ui.customers').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.customer_ui.customers'))

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ __('app.customer_ui.workspace_operations') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.customer_ui.customers') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.customer_ui.list_help') }}</p>
            </div>
            @can('customers.create')
                <a href="#customer-form" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                    {{ __('app.customer_ui.add_customer') }}
                </a>
            @endcan
        </section>

        <section class="grid gap-3 sm:grid-cols-2">
            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.customer_ui.total_customers') }}</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ number_format($customers->total()) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.customer_ui.total_customers_help') }}</p>
            </article>
            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.customer_ui.search') }}</p>
                <p class="mt-2 truncate text-lg font-bold text-slate-950 dark:text-white">{{ $search !== '' ? $search : __('app.customer_ui.all_customers') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.customer_ui.search_help') }}</p>
            </article>
        </section>

        @can('customers.create')
            <section id="customer-form" class="br-panel p-5 sm:p-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.customer_ui.new_record') }}</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ __('app.customer_ui.add_customer') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.customer_ui.add_help') }}</p>
                </div>

                <form method="POST" action="{{ route('customers.store') }}" class="mt-5 grid gap-4 lg:grid-cols-3">
                    @csrf
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('app.customer_ui.name') }}</span>
                        <input name="name" type="text" value="{{ old('name') }}" required maxlength="160"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('app.customer_ui.phone') }}</span>
                        <input name="phone" type="tel" value="{{ old('phone') }}" inputmode="tel"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('app.customer_ui.email') }}</span>
                        <input name="email" type="email" value="{{ old('email') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <div class="lg:col-span-3 flex justify-end">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-navy px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">
                            {{ __('app.customer_ui.save_customer') }}
                        </button>
                    </div>
                </form>
            </section>
        @endcan

        <section class="br-panel overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.customer_ui.customer_list') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.customer_ui.customer_list_help') }}</p>
                </div>

                <form method="GET" action="{{ route('customers.index') }}" class="flex w-full gap-2 sm:w-auto">
                    <label class="sr-only" for="customer-search">{{ __('app.customer_ui.search') }}</label>
                    <input id="customer-search" name="search" value="{{ $search }}" type="search"
                           placeholder="{{ __('app.customer_ui.search_placeholder') }}"
                           class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 sm:w-72">
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.customer_ui.search') }}
                    </button>
                </form>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($customers as $customer)
                    <a href="{{ route('customers.show', $customer) }}" class="block p-5 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-sm font-extrabold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">
                                    {{ str($customer->name)->substr(0, 1)->upper() }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="truncate font-bold text-slate-950 dark:text-white">{{ $customer->name }}</h4>
                                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                                        @if ($customer->phone)<span>{{ $customer->phone }}</span>@endif
                                        @if ($customer->email)<span class="truncate">{{ $customer->email }}</span>@endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-3 text-end">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.customer_ui.bookings') }}</p>
                                    <p class="mt-1 text-lg font-extrabold text-slate-950 dark:text-white">{{ number_format($customer->bookings_count) }}</p>
                                </div>
                                <span class="br-direction-arrow text-xl text-slate-300 dark:text-slate-600" aria-hidden="true">→</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-14 text-center">
                        <p class="font-bold text-slate-950 dark:text-white">{{ __('app.customer_ui.no_customers') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $search !== '' ? __('app.customer_ui.no_search_results') : __('app.customer_ui.no_customers_help') }}</p>
                        @can('customers.create')
                            <a href="#customer-form" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                                {{ __('app.customer_ui.add_customer') }}
                            </a>
                        @endcan
                    </div>
                @endforelse
            </div>

            @if ($customers->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                    {{ $customers->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
