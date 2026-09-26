<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $businessName = data_get($tenant->profile?->name, app()->getLocale())
            ?? data_get($tenant->profile?->name, 'en')
            ?? $tenant->slug;
        $payment = $booking->payments->sortByDesc('id')->first();
        $paymentStatus = $payment?->status?->value;
    @endphp

    <x-seo
        :title="$booking->booking_reference.' — '.$businessName"
        :description="__('app.public_booking_ui.confirmation_meta', ['reference' => $booking->booking_reference])"
        robots="noindex,nofollow,noarchive"
        og-type="website"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10 sm:px-6">
        <section class="w-full rounded-[2rem] border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900 sm:p-10">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('public.booking.show', $tenant->slug) }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-navy text-sm font-extrabold text-white">B</span>
                    <span class="text-sm font-extrabold">BookResa</span>
                </a>
                <div class="flex items-center gap-2">
                    <x-locale-switcher />
                    <x-theme-toggle />
                </div>
            </div>

            <div class="mt-10 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">✓</div>
                <p class="mt-6 text-xs font-bold uppercase tracking-[0.16em] text-brand-indigo">
                    {{ $booking->status->value === 'confirmed' ? __('app.booking_confirmed') : __('app.booking_received') }}
                </p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">{{ $booking->booking_reference }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">{{ __('app.public_booking_ui.confirmation_message') }}</p>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl br-surface-soft p-4"><p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.public_booking_ui.business') }}</p><p class="mt-1 font-bold">{{ $businessName }}</p></div>
                <div class="rounded-2xl br-surface-soft p-4"><p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.public_booking_ui.service') }}</p><p class="mt-1 font-bold">{{ data_get($booking->service->name, app()->getLocale()) ?? data_get($booking->service->name, 'en') }}</p></div>
                <div class="rounded-2xl br-surface-soft p-4"><p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.public_booking_ui.date_time') }}</p><p class="mt-1 font-bold">{{ $booking->starts_at->setTimezone($tenant->profile?->timezone ?? 'UTC')->format('d M Y, H:i') }}</p></div>
                <div class="rounded-2xl br-surface-soft p-4"><p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.public_booking_ui.customer') }}</p><p class="mt-1 font-bold">{{ $booking->customer->name }}</p></div>
            </div>

            @if ($payment)
                <div class="mt-5 rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.payment_status') }}</p>
                            <p class="mt-1 font-bold">{{ str($paymentStatus)->replace('_', ' ')->title() }}</p>
                        </div>
                        <p class="font-extrabold">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</p>
                    </div>

                    @if ($paymentStatus !== 'paid' && $payment->checkout_url)
                        <a href="{{ $payment->checkout_url }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                            {{ __('app.complete_payment') }}
                        </a>
                    @endif
                </div>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('public.booking.show', $tenant->slug) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-brand-navy px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">{{ __('app.book_another_appointment') }}</a>
                <a href="{{ route('home') }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.public_booking_ui.visit_bookresa') }}</a>
            </div>
        </section>
    </main>
</body>
</html>
