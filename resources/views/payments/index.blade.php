@extends('layouts.dashboard')

@section('title', __('Payments').' — '.config('bookresa.name', 'Velto'))
@section('heading', __('Payments'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ $tenant->slug }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Customer payments') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('View payment activity linked to your business bookings.') }}</p>
        </div>

        <form method="GET" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="w-full sm:max-w-xs">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</span>
                    <select name="status" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ str($status->value)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                    {{ __('Apply filter') }}
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-950/50">
                    <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3 text-start">{{ __('Reference') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('Customer') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('Booking') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('Amount') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('Date') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($payments as $payment)
                        @php $booking = $payment->payable; @endphp
                        <tr>
                            <td class="px-4 py-4 font-mono text-xs font-semibold">{{ $payment->reference }}</td>
                            <td class="px-4 py-4">
                                <p class="font-medium">{{ $booking?->customer?->name ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $booking?->customer?->phone ?? $booking?->customer?->email ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                @if ($booking)
                                    <a href="{{ route('booking.management.show', $booking) }}" class="font-semibold underline underline-offset-4">{{ $booking->booking_reference }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-4 font-semibold">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800">
                                    {{ str($payment->status->value)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-500">{{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">{{ __('No customer payments yet.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($payments->hasPages())
                <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
@endsection
