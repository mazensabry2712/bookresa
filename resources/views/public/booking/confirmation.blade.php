<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Confirmed — BookResa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <main class="mx-auto max-w-xl px-6 py-16 text-center">
        <p class="text-sm uppercase tracking-wide text-gray-500">Booking confirmed</p>
        <h1 class="mt-3 text-4xl font-bold">{{ $booking->booking_reference }}</h1>
        <p class="mt-4 text-gray-600 dark:text-gray-400">
            {{ $booking->service->name['en'] ?? 'Service' }}
            ·
            {{ $booking->starts_at->setTimezone($tenant->profile?->timezone ?? 'UTC')->format('Y-m-d H:i') }}
        </p>
        <a href="{{ route('public.booking.show', $tenant->slug) }}"
            class="mt-8 inline-flex rounded-lg bg-black px-5 py-3 font-medium text-white dark:bg-white dark:text-black">
            Book another appointment
        </a>
    </main>
</body>
</html>
