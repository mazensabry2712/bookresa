@extends('layouts.dashboard')

@section('title', __('Bookings').' — '.config('app.name', 'BookResa'))
@section('heading', __('Bookings'))

@section('content')
    @php
        $statusLabel = fn ($value) => str($value)->replace('_', ' ')->title();
        $serviceName = fn ($service) => data_get($service?->name, app()->getLocale())
            ?? data_get($service?->name, 'en')
            ?? '—';
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Booking management') }}</h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ __('Review upcoming appointments, filter the schedule and manage the booking lifecycle.') }}</p>
            </div>
            <div class="rounded-2xl bg-white px-4 py-3 text-sm shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <span class="font-semibold">{{ $bookings->total() }}</span>
                <span class="text-slate-500">{{ __('bookings') }}</span>
            </div>
        </div>

        <form method="GET" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                <label class="lg:col-span-2">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</span>
                    <input
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Reference, customer name, phone or email') }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-slate-900 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-950"
                    >
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</span>
                    <select name="status" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ $statusLabel($status->value) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Service') }}</span>
                    <select name="service_id" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All services') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) request('service_id') === (string) $service->id)>
                                {{ $serviceName($service) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Staff') }}</span>
                    <select name="staff_id" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All staff') }}</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>
                                {{ $member->display_name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Date') }}</span>
                    <input
                        type="date"
                        name="date"
                        value="{{ request('date') }}"
                        class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950"
                    >
                </label>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    {{ __('Apply filters') }}
                </button>
                <a href="{{ route('booking.management.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-950/50">
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">{{ __('Reference') }}</th>
                        <th class="px-4 py-3">{{ __('Customer') }}</th>
                        <th class="px-4 py-3">{{ __('Service') }}</th>
                        <th class="px-4 py-3">{{ __('Staff') }}</th>
                        <th class="px-4 py-3">{{ __('Starts') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($bookings as $booking)
                        @php
                            $status = $booking->status->value;
                            $badge = match ($status) {
                                'pending' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900',
                                'confirmed' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-900',
                                'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900',
                                'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900',
                                'no_show' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
                                default => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/40">
                            <td class="whitespace-nowrap px-4 py-4 font-mono text-xs font-semibold">
                                {{ $booking->booking_reference }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium">{{ $booking->customer?->name ?? '—' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $booking->customer?->phone ?? $booking->customer?->email ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                {{ $serviceName($booking->service) }}
                            </td>
                            <td class="px-4 py-4">
                                {{ $booking->staff?->display_name ?? __('Auto assigned') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                {{ $booking->starts_at?->setTimezone($timezone)->format('d M Y, H:i') }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $badge }}">
                                    {{ $statusLabel($status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-end">
                                <a href="{{ route('booking.management.show', $booking) }}" class="font-semibold text-slate-900 hover:underline dark:text-white">
                                    {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <p class="font-semibold">{{ __('No bookings found') }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ __('Try changing your filters or create a new public booking.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($bookings->hasPages())
                <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
