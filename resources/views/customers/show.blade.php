@extends('layouts.dashboard')

@section('title', $customer->name.' — '.__('Customer'))
@section('heading', __('Customer'))

@section('content')
    <div class="space-y-6">
        <div>
            <a href="{{ route('customers.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white">
                ← {{ __('Back to customers') }}
            </a>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">{{ $customer->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $customer->bookings_count }} {{ __('bookings') }}
                    </p>
                </div>
                <div class="rounded-2xl bg-white px-4 py-3 text-end shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Last seen') }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ $customer->last_seen_at?->diffForHumans() ?? '—' }}</p>
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5">
                <h3 class="font-semibold">{{ __('Customer details') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Update contact details without changing booking history.') }}</p>
            </div>

            @can('customers.update')
                <form method="POST" action="{{ route('customers.update', $customer) }}" class="grid gap-4 md:grid-cols-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $customer->name) }}" required maxlength="160"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium">{{ __('Phone') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $customer->phone) }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium">{{ __('Email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit"
                                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                            {{ __('Save customer changes') }}
                        </button>
                    </div>
                </form>
            @else
                <dl class="grid gap-4 md:grid-cols-3">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</dt><dd class="mt-1">{{ $customer->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Phone') }}</dt><dd class="mt-1">{{ $customer->phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Email') }}</dt><dd class="mt-1">{{ $customer->email ?? '—' }}</dd></div>
                </dl>
            @endcan
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h3 class="font-semibold">{{ __('Booking history') }}</h3>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($bookings as $booking)
                    @php
                        $serviceName = data_get($booking->service?->name, app()->getLocale())
                            ?? data_get($booking->service?->name, 'en')
                            ?? '—';
                    @endphp
                    <a href="{{ route('booking.management.show', $booking) }}"
                       class="block px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/50">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold">{{ $serviceName }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $booking->starts_at?->format('d M Y, H:i') }}
                                    · {{ str($booking->status->value)->replace('_', ' ')->title() }}
                                </p>
                            </div>
                            <span class="font-mono text-xs text-slate-500">{{ $booking->booking_reference }}</span>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-sm text-slate-500">{{ __('No bookings yet.') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($bookings->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                    {{ $bookings->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
