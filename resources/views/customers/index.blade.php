@extends('layouts.dashboard')

@section('title', __('Customers'))
@section('heading', __('Customers'))

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5">
                <h2 class="text-base font-semibold">{{ __('Add customer') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Keep customer details ready for the next booking.') }}
                </p>
            </div>

            @can('customers.create')
                <form method="POST" action="{{ route('customers.store') }}" class="grid gap-4 md:grid-cols-3">
                    @csrf

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="160"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium">{{ __('Phone') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium">{{ __('Email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit"
                                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                            {{ __('Add customer') }}
                        </button>
                    </div>
                </form>
            @endcan
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div>
                    <h2 class="font-semibold">{{ __('Customer list') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Search by name, phone, or email.') }}</p>
                </div>

                <form method="GET" action="{{ route('customers.index') }}" class="flex w-full gap-2 sm:w-auto">
                    <label class="sr-only" for="customer-search">{{ __('Search') }}</label>
                    <input id="customer-search" name="search" value="{{ $search }}" type="search"
                           placeholder="{{ __('Search customers') }}"
                           class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 sm:w-72">
                    <button type="submit"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold dark:border-slate-700">
                        {{ __('Search') }}
                    </button>
                </form>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($customers as $customer)
                    <a href="{{ route('customers.show', $customer) }}"
                       class="block px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/50">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $customer->name }}</p>
                                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                                    @if ($customer->phone)
                                        <span>{{ $customer->phone }}</span>
                                    @endif
                                    @if ($customer->email)
                                        <span class="truncate">{{ $customer->email }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="shrink-0 text-sm text-slate-500">
                                {{ $customer->bookings_count }} {{ __('bookings') }}
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-sm text-slate-500">{{ __('No customers yet.') }}</p>
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
