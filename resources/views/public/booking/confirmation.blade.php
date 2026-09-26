<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-seo
        :title="'Booking '.$booking->booking_reference.' — Velto'"
        :description="'Booking confirmation for '.$booking->booking_reference.'.'"
        robots="noindex,nofollow,noarchive"
        og-type="website"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <main class="mx-auto max-w-xl px-6 py-16 text-center">
        <div class="mb-8 flex items-center justify-center gap-2">
            <x-theme-toggle />
        </div>
        @if (session('payment_notice'))
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900">
                {{ session('payment_notice') }}
            </div>
        @endif

        <p class="text-sm uppercase tracking-wide text-gray-500">
            {{ $booking->status->value === 'confirmed' ? __('app.booking_confirmed') : __('app.booking_received') }}
        </p>
        <h1 class="mt-3 text-4xl font-bold">{{ $booking->booking_reference }}</h1>
        <p class="mt-4 text-gray-600 dark:text-gray-400">
            {{ $booking->service->name[app()->getLocale()] ?? $booking->service->name['en'] ?? __('app.service') }}
            ·
            {{ $booking->starts_at->setTimezone($tenant->profile?->timezone ?? 'UTC')->format('Y-m-d H:i') }}
        </p>
        @php($payment = $booking->payments->sortByDesc('id')->first())

        @if ($payment)
            <div class="mt-6 rounded-xl border border-gray-200 p-4 text-left dark:border-gray-800">
                <p class="text-sm text-gray-500">{{ __('app.payment_status') }}</p>
                <p class="mt-1 font-semibold">{{ str($payment->status->value)->headline() }}</p>

                @if ($payment->status->value !== 'paid' && $payment->checkout_url)
                    <a href="{{ $payment->checkout_url }}"
                        class="mt-4 inline-flex w-full justify-center rounded-lg bg-black px-5 py-3 font-medium text-white dark:bg-white dark:text-black">
                        {{ __('app.complete_payment') }}
                    </a>
                @endif
            </div>
        @endif

        <a href="{{ route('public.booking.show', $tenant->slug) }}"
            class="mt-8 inline-flex rounded-lg bg-black px-5 py-3 font-medium text-white dark:bg-white dark:text-black">
            {{ __('app.book_another_appointment') }}
        </a>
    </main>
</body>
</html>
