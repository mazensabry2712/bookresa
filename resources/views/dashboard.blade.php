@extends('layouts.dashboard')

@section('title', __('app.dashboard').' — '.config('bookresa.name', 'BookResa'))
@section('heading', __('app.dashboard'))

@section('content')
    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $money = static fn (?int $minor, string $currency = 'EGP'): string => $minor === null
            ? '—'
            : number_format($minor / 100, 2).' '.$currency;

        $planName = $localized($subscription?->plan?->name);

        $statusLabels = [
            'pending' => __('app.dashboard_ui.status_pending'),
            'confirmed' => __('app.dashboard_ui.status_confirmed'),
            'rescheduled' => __('app.dashboard_ui.status_rescheduled'),
            'completed' => __('app.dashboard_ui.status_completed'),
            'cancelled' => __('app.dashboard_ui.status_cancelled'),
            'no_show' => __('app.dashboard_ui.status_no_show'),
        ];

        $statusClasses = [
            'pending' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
            'confirmed' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-200',
            'rescheduled' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-200',
            'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200',
            'cancelled' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
            'no_show' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200',
        ];

        $paymentLabels = [
            'unpaid' => __('app.dashboard_ui.payment_unpaid'),
            'partially_paid' => __('app.dashboard_ui.payment_partial'),
            'paid' => __('app.dashboard_ui.payment_paid'),
            'refunded' => __('app.dashboard_ui.payment_refunded'),
        ];

        $paymentClasses = [
            'unpaid' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200',
            'partially_paid' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
            'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200',
            'refunded' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ];

        $subscriptionStatusLabels = [
            'trial' => __('app.dashboard_ui.status_trial'),
            'active' => __('app.dashboard_ui.status_active'),
            'cancellation_scheduled' => __('app.dashboard_ui.status_cancellation_scheduled'),
        ];

        $subscriptionStatusClasses = [
            'trial' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
            'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-300',
            'cancellation_scheduled' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200',
        ];

        $summaryMetricCount = 3 + (int) $canViewRevenue + (int) $canViewActiveStaff;
        $summaryGridClass = match ($summaryMetricCount) {
            5 => 'sm:grid-cols-2 xl:grid-cols-5',
            4 => 'sm:grid-cols-2 xl:grid-cols-4',
            default => 'sm:grid-cols-2 xl:grid-cols-3',
        };

        $usagePercent = $attention['usagePercent'] ?? 0;
        $bookingUrl = route('public.booking.canonical.show', ['tenant' => $tenant->slug]);
        $bookingLogoUrl = $tenant->profile?->logo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($tenant->profile->logo_path)
            : '';
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.business_name') }}</p>
                <h2 class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    {{ $businessName }}
                </h2>
                <p class="mt-2 text-sm font-semibold text-brand-indigo">{{ $todayLabel }}</p>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    {{ __('app.dashboard_ui.welcome', ['name' => auth()->user()->name]) }}. {{ __('app.dashboard_ui.workspace_summary') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('bookings.create')
                    <a href="{{ route('booking.management.create') }}"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                        {{ __('app.booking_ui.new_booking') }}
                    </a>
                @endcan

                @can('bookings.view')
                    <a href="{{ route('booking.management.index') }}"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.dashboard_ui.open_bookings') }}
                    </a>
                @endcan

                <a href="{{ route('public.booking.canonical.show', ['tenant' => $tenant->slug]) }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('app.dashboard_ui.view_booking_page') }}
                </a>
            </div>
        </section>


        <section class="br-panel overflow-hidden" aria-labelledby="booking-share-title" data-bookresa-booking-share>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.dashboard_ui.booking_share_eyebrow') }}</p>
                    <h3 id="booking-share-title" class="mt-2 text-xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                        {{ __('app.dashboard_ui.booking_share_title') }}
                    </h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        {{ __('app.dashboard_ui.booking_share_help') }}
                    </p>

                    <label for="dashboard-booking-url" class="mt-5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('app.dashboard_ui.booking_link') }}
                    </label>
                    <input id="dashboard-booking-url" type="url" value="{{ $bookingUrl }}" readonly
                           data-bookresa-dashboard-booking-url
                           class="mt-2 w-full min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                           aria-label="{{ __('app.dashboard_ui.booking_link') }}">

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" data-bookresa-dashboard-copy-url
                                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            {{ __('app.dashboard_ui.copy_booking_link') }}
                        </button>
                        <a href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                            {{ __('app.dashboard_ui.view_booking_page') }}
                        </a>
                    </div>
                    <p class="mt-2 min-h-5 text-sm text-slate-500 dark:text-slate-400" data-bookresa-dashboard-copy-feedback aria-live="polite"></p>
                </div>

                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 text-center dark:border-indigo-900/50 dark:bg-indigo-950/20 sm:p-5">
                    <div class="flex items-center justify-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white text-brand-indigo shadow-sm dark:bg-slate-900" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="6.5" height="6.5" rx="1"/><rect x="14" y="3.5" width="6.5" height="6.5" rx="1"/><rect x="3.5" y="14" width="6.5" height="6.5" rx="1"/><path stroke-linecap="round" d="M14 14h2v2h-2zM18 14h2.5M18 18h2.5M14 20.5h2"/></svg>
                        </span>
                        <h4 class="font-bold text-slate-950 dark:text-white">{{ __('app.dashboard_ui.qr_code') }}</h4>
                    </div>

                    <div class="mt-4 flex min-h-[17rem] items-center justify-center rounded-xl bg-white p-3 shadow-sm dark:bg-slate-950">
                        <div data-bookresa-dashboard-booking-qr
                             data-booking-url="{{ $bookingUrl }}"
                             data-business-name="{{ $businessName }}"
                             data-business-logo="{{ $bookingLogoUrl }}"
                             data-business-slug="{{ $tenant->slug }}"
                             data-locale="{{ app()->getLocale() }}"
                             class="flex min-h-60 min-w-60 items-center justify-center"
                             aria-label="{{ __('app.dashboard_ui.qr_code') }}"></div>
                    </div>

                    <button type="button" data-bookresa-download-booking-poster disabled
                            class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-indigo-900 dark:bg-slate-950 dark:text-indigo-300 dark:hover:bg-indigo-950/40">
                        {{ __('app.dashboard_ui.download_booking_poster') }}
                    </button>
                    <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('app.dashboard_ui.qr_poster_help') }}</p>
                    <p class="mt-2 min-h-5 text-xs text-indigo-700 dark:text-indigo-300" data-bookresa-dashboard-poster-feedback aria-live="polite"></p>
                </div>
            </div>
        </section>

        <script src="{{ asset('js/qrcode.min.js') }}"></script>
        <script>
            (() => {
                const qrBox = document.querySelector('[data-bookresa-dashboard-booking-qr]');
                if (!qrBox) return;

                const bookingUrl = qrBox.dataset.bookingUrl || '';
                const businessName = qrBox.dataset.businessName || 'BookResa';
                const businessLogoUrl = qrBox.dataset.businessLogo || '';
                const businessSlug = qrBox.dataset.businessSlug || 'workspace';
                const locale = qrBox.dataset.locale || 'en';
                const isArabic = locale === 'ar';
                const urlInput = document.querySelector('[data-bookresa-dashboard-booking-url]');
                const copyButton = document.querySelector('[data-bookresa-dashboard-copy-url]');
                const copyFeedback = document.querySelector('[data-bookresa-dashboard-copy-feedback]');
                const downloadButton = document.querySelector('[data-bookresa-download-booking-poster]');
                const posterFeedback = document.querySelector('[data-bookresa-dashboard-poster-feedback]');

                const messages = {
                    copied: @json(__('app.dashboard_ui.booking_link_copied')),
                    copyFallback: @json(__('app.dashboard_ui.booking_link_copy_fallback')),
                    qrError: @json(__('app.dashboard_ui.qr_poster_error')),
                    posterError: @json(__('app.dashboard_ui.qr_poster_download_error')),
                    posterDownloaded: @json(__('app.dashboard_ui.qr_poster_downloaded')),
                    scanCaption: @json(__('app.dashboard_ui.qr_scan_caption')),
                    posterHeading: @json(__('app.dashboard_ui.qr_poster_heading'))
                };

                try {
                    new QRCode(qrBox, {
                        text: bookingUrl,
                        width: 240,
                        height: 240,
                        colorDark: '#1E2A44',
                        colorLight: '#FFFFFF',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    if (downloadButton) downloadButton.disabled = false;
                } catch (error) {
                    if (posterFeedback) posterFeedback.textContent = messages.qrError;
                }

                if (copyButton && urlInput && copyFeedback) {
                    copyButton.addEventListener('click', async () => {
                        try {
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(bookingUrl);
                            } else {
                                throw new Error('Clipboard API unavailable');
                            }
                            copyFeedback.textContent = messages.copied;
                        } catch (error) {
                            urlInput.focus();
                            urlInput.select();
                            let copied = false;
                            try {
                                copied = document.execCommand('copy');
                            } catch (copyError) {
                                copied = false;
                            }
                            copyFeedback.textContent = copied ? messages.copied : messages.copyFallback;
                        }
                    });
                }

                function roundedRect(context, x, y, width, height, radius) {
                    const r = Math.min(radius, width / 2, height / 2);
                    context.beginPath();
                    context.moveTo(x + r, y);
                    context.lineTo(x + width - r, y);
                    context.quadraticCurveTo(x + width, y, x + width, y + r);
                    context.lineTo(x + width, y + height - r);
                    context.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
                    context.lineTo(x + r, y + height);
                    context.quadraticCurveTo(x, y + height, x, y + height - r);
                    context.lineTo(x, y + r);
                    context.quadraticCurveTo(x, y, x + r, y);
                    context.closePath();
                }

                async function loadLogo() {
                    if (!businessLogoUrl) return null;
                    return new Promise((resolve) => {
                        const image = new Image();
                        image.onload = () => resolve(image);
                        image.onerror = () => resolve(null);
                        image.src = businessLogoUrl;
                        if (image.complete && image.naturalWidth > 0) resolve(image);
                    });
                }

                async function getQrCanvas() {
                    const existingCanvas = qrBox.querySelector('canvas');
                    if (existingCanvas && existingCanvas.width > 0) return existingCanvas;

                    const image = qrBox.querySelector('img');
                    if (!image) throw new Error('QR drawing not available');
                    if (!image.complete) {
                        await new Promise((resolve, reject) => {
                            image.onload = resolve;
                            image.onerror = reject;
                        });
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = image.naturalWidth || 240;
                    canvas.height = image.naturalHeight || 240;
                    const context = canvas.getContext('2d');
                    context.drawImage(image, 0, 0, canvas.width, canvas.height);
                    return canvas;
                }

                async function downloadPoster() {
                    if (!bookingUrl || !qrBox.querySelector('canvas, img')) {
                        posterFeedback.textContent = messages.qrError;
                        return;
                    }

                    try {
                        const qrCanvas = await getQrCanvas();
                        const canvas = document.createElement('canvas');
                        canvas.width = 1200;
                        canvas.height = 1500;
                        const context = canvas.getContext('2d');
                        if (!context) throw new Error('Canvas unavailable');

                        context.fillStyle = '#F3F5FB';
                        context.fillRect(0, 0, canvas.width, canvas.height);
                        context.strokeStyle = '#DEE5F2';
                        context.lineWidth = 4;
                        roundedRect(context, 24, 24, 1152, 1452, 44);
                        context.stroke();

                        const headerGradient = context.createLinearGradient(60, 60, 1140, 370);
                        headerGradient.addColorStop(0, '#1E2A44');
                        headerGradient.addColorStop(1, '#4F46E5');
                        context.fillStyle = headerGradient;
                        roundedRect(context, 60, 60, 1080, 320, 42);
                        context.fill();

                        const logo = await loadLogo();
                        if (logo) {
                            context.save();
                            roundedRect(context, 540, 82, 120, 120, 28);
                            context.clip();
                            context.drawImage(logo, 540, 82, 120, 120);
                            context.restore();
                        } else {
                            context.fillStyle = '#FFFFFF';
                            roundedRect(context, 540, 82, 120, 120, 30);
                            context.fill();
                            context.fillStyle = '#1E2A44';
                            context.font = '700 70px Arial, Tahoma, sans-serif';
                            context.textAlign = 'center';
                            context.direction = isArabic ? 'rtl' : 'ltr';
                            context.fillText(Array.from(businessName.trim())[0] || 'B', 600, 163, 98);
                        }

                        context.textAlign = 'center';
                        context.direction = isArabic ? 'rtl' : 'ltr';
                        context.fillStyle = '#FFFFFF';
                        context.font = '700 54px Arial, Tahoma, sans-serif';
                        context.fillText(businessName, 600, 260, 990);
                        context.font = '500 27px Arial, Tahoma, sans-serif';
                        context.fillStyle = '#E6E9FF';
                        context.fillText(messages.posterHeading, 600, 315, 990);

                        context.shadowColor = 'rgba(30,42,68,0.10)';
                        context.shadowBlur = 28;
                        context.shadowOffsetY = 10;
                        context.fillStyle = '#FFFFFF';
                        roundedRect(context, 170, 410, 860, 720, 38);
                        context.fill();
                        context.shadowColor = 'transparent';
                        context.shadowBlur = 0;
                        context.shadowOffsetY = 0;

                        context.fillStyle = '#FFFFFF';
                        roundedRect(context, 330, 445, 540, 540, 24);
                        context.fill();
                        context.imageSmoothingEnabled = false;
                        context.drawImage(qrCanvas, 360, 475, 480, 480);
                        context.imageSmoothingEnabled = true;

                        context.textAlign = 'center';
                        context.direction = isArabic ? 'rtl' : 'ltr';
                        context.fillStyle = '#1E2A44';
                        context.font = '700 34px Arial, Tahoma, sans-serif';
                        context.fillText(messages.scanCaption, 600, 1025, 780);
                        context.fillStyle = '#536079';
                        context.font = '500 20px Arial, Tahoma, sans-serif';
                        context.direction = 'ltr';
                        context.fillText(bookingUrl, 600, 1070, 790);

                        context.fillStyle = '#1E2A44';
                        context.font = '700 29px Arial, Tahoma, sans-serif';
                        context.fillText('BookResa', 600, 1210);
                        context.fillStyle = '#6B7489';
                        context.font = '500 20px Arial, Tahoma, sans-serif';
                        context.fillText(bookingUrl.replace(/^https?:\/\//i, ''), 600, 1250, 900);
                        context.fillStyle = '#8891A5';
                        context.font = '500 18px Arial, Tahoma, sans-serif';
                        context.fillText('BOOKING QR', 600, 1400);

                        canvas.toBlob((blob) => {
                            if (!blob) {
                                posterFeedback.textContent = messages.posterError;
                                return;
                            }
                            const objectUrl = URL.createObjectURL(blob);
                            const anchor = document.createElement('a');
                            const safeSlug = businessSlug.replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '').toLowerCase() || 'workspace';
                            anchor.href = objectUrl;
                            anchor.download = 'bookresa-booking-qr-' + safeSlug + '.png';
                            document.body.appendChild(anchor);
                            anchor.click();
                            anchor.remove();
                            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1500);
                            posterFeedback.textContent = messages.posterDownloaded;
                        }, 'image/png');
                    } catch (error) {
                        posterFeedback.textContent = messages.posterError;
                    }
                }

                if (downloadButton) {
                    downloadButton.addEventListener('click', downloadPoster);
                }
            })();
        </script>

        @if (! $onboarding['completed'])
            <section class="br-panel overflow-hidden" aria-labelledby="setup-title">
                <div class="flex flex-col gap-4 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand-navy dark:bg-indigo-950/40 dark:text-indigo-300" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/><circle cx="18" cy="17" r="3"/></svg>
                            </span>
                            <h3 id="setup-title" class="font-semibold text-slate-950 dark:text-white">{{ __('app.your_setup') }}</h3>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            {{ __('app.step_progress', ['current' => $onboarding['currentStage'], 'total' => 4]) }}
                            <span aria-hidden="true">·</span>
                            {{ __('app.onboarding_steps.'.($onboarding['step'] === 'services' ? 'services' : ($onboarding['step'] === 'hours' ? 'hours' : 'staff'))) }}
                        </p>
                        <div class="mt-3 h-1.5 max-w-xl overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                             role="progressbar"
                             aria-valuemin="1"
                             aria-valuemax="4"
                             aria-valuenow="{{ $onboarding['currentStage'] }}"
                             aria-label="{{ __('app.step_progress', ['current' => $onboarding['currentStage'], 'total' => 4]) }}">
                            <div class="h-full rounded-full bg-brand-indigo transition-[width] duration-300"
                                 style="width: {{ ($onboarding['currentStage'] / 4) * 100 }}%"></div>
                        </div>
                    </div>

                    <a href="{{ route($onboarding['route']) }}"
                       class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                        {{ __('app.continue') }}
                    </a>
                </div>
            </section>
        @endif

        <section class="grid gap-4 {{ $summaryGridClass }}" aria-label="{{ __('app.dashboard_ui.summary') }}">
            <article class="br-panel min-h-[148px] p-5 transition-shadow duration-150 hover:shadow-md">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-brand-indigo dark:bg-indigo-950/40 dark:text-indigo-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="4.5" width="17" height="16" rx="2.5"/><path stroke-linecap="round" d="M8 2.75v3.5M16 2.75v3.5M3.5 9h17"/><path stroke-linecap="round" d="M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01M16 17h.01"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('app.dashboard_ui.today') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('app.dashboard_ui.today_bookings') }}</p>
                <p data-dashboard-metric="today-bookings" data-metric-value="{{ $metrics['todayBookings'] }}" class="mt-1 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ number_format($metrics['todayBookings']) }}</p>
            </article>

            @if ($canViewRevenue)
            <article class="br-panel min-h-[148px] p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3.5 2"/><path stroke-linecap="round" d="M8.5 3.9 7 2.5M15.5 3.9 17 2.5"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('app.dashboard_ui.paid') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('app.dashboard_ui.today_revenue') }}</p>
                <div data-dashboard-metric="today-revenue" data-metric-value="{{ collect($metrics['todayRevenueByCurrency'])->map(fn ($minor, $currency) => $currency.':'.(int) $minor)->implode('|') }}" class="mt-1 space-y-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                    @forelse ($metrics['todayRevenueByCurrency'] as $currency => $amountMinor)
                        <p>{{ $money((int) $amountMinor, (string) $currency) }}</p>
                    @empty
                        <p aria-label="{{ __('app.dashboard_ui.no_paid_revenue') }}">—</p>
                    @endforelse
                </div>
            </article>
            @endif

            <article class="br-panel min-h-[148px] p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 5.5h11A1.5 1.5 0 0 1 19 7v11.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 18.5V7a1.5 1.5 0 0 1 1.5-1.5ZM9 3v5M15 3v5M5 10h14"/><path stroke-linecap="round" d="M9 14h6M9 17h3"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('app.dashboard_ui.upcoming') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('app.dashboard_ui.upcoming_bookings') }}</p>
                <p data-dashboard-metric="upcoming-bookings" data-metric-value="{{ $metrics['upcomingBookings'] }}" class="mt-1 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ number_format($metrics['upcomingBookings']) }}</p>
            </article>

            <article class="br-panel min-h-[148px] p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20"/><circle cx="10" cy="7.5" r="3.25"/><path stroke-linecap="round" d="M16 11h4M18 9v4"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('app.customers') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('app.customers') }}</p>
                <div class="mt-1 flex items-end justify-between gap-3">
                    <p data-dashboard-metric="customers" data-metric-value="{{ $metrics['customers'] }}" class="text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ number_format($metrics['customers']) }}</p>
                    <span class="shrink-0 text-xs font-semibold text-slate-400">
                        +{{ number_format($metrics['newCustomersToday']) }} {{ __('app.dashboard_ui.today') }}
                    </span>
                </div>
            </article>

            @if ($canViewActiveStaff)
            <article class="br-panel min-h-[148px] p-5">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.25"/><path stroke-linecap="round" d="M3.5 20a5.5 5.5 0 0 1 11 0M16 12.5a4.5 4.5 0 0 1 4.5 4.5"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-400">{{ __('app.dashboard_ui.active_staff') }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('app.dashboard_ui.active_staff') }}</p>
                <p data-dashboard-metric="active-staff" data-metric-value="{{ $metrics['activeStaff'] }}" class="mt-1 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">{{ number_format($metrics['activeStaff']) }}</p>
            </article>
            @endif
        </section>

        @if (
            $attention['pendingBookings'] > 0
            || $attention['unpaidBookings'] > 0
            || $attention['subscriptionNeedsAction']
            || $attention['usageOverLimit']
            || (($attention['usagePercent'] ?? 0) >= 80)
        )
        <section class="br-panel overflow-hidden" aria-labelledby="attention-title">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <h3 id="attention-title" class="font-semibold text-slate-950 dark:text-white">{{ __('app.dashboard_ui.attention') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.dashboard_ui.attention_description') }}</p>
                </div>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @if ($attention['pendingBookings'] > 0)
                    <a href="{{ route('booking.management.index') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/><path stroke-linecap="round" d="M8.5 3.9 7 2.5M15.5 3.9 17 2.5"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ trans_choice('app.dashboard_ui.pending_bookings_count', $attention['pendingBookings']) }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.dashboard_ui.pending_bookings_help') }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('app.dashboard_ui.review') }}</span>
                    </a>
                @endif

                @if ($attention['unpaidBookings'] > 0)
                    <a href="{{ route('booking.management.index') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"/><path stroke-linecap="round" d="M4 9h16M8 14h4M8 16.5h2"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ trans_choice('app.dashboard_ui.unpaid_bookings_count', $attention['unpaidBookings']) }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.dashboard_ui.unpaid_bookings_help') }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('app.dashboard_ui.review') }}</span>
                    </a>
                @endif

                @can('billing.view')
                @if ($attention['subscriptionNeedsAction'])
                    <a href="{{ route('billing.subscription') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"/><path stroke-linecap="round" d="M4 9h16M8 13h8M8 16h5"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    @if ($subscription)
                                        {{ __('app.dashboard_ui.subscription_expires_soon') }}
                                    @else
                                        {{ __('app.dashboard_ui.no_active_subscription') }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    @if ($subscription)
                                        {{ trans_choice('app.dashboard_ui.subscription_days_left', $subscriptionDaysRemaining ?? 0, ['count' => $subscriptionDaysRemaining ?? 0]) }}
                                    @else
                                        {{ __('app.dashboard_ui.subscription_action_help') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('app.dashboard_ui.manage') }}</span>
                    </a>
                @endif

                @if ($attention['usageOverLimit'] || (($attention['usagePercent'] ?? 0) >= 80))
                    <a href="{{ route('billing.subscription') }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300" aria-hidden="true">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5 20 19H4L12 3.5Z"/><path stroke-linecap="round" d="M12 9v4M12 16.5v.01"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    @if ($attention['usageOverLimit'])
                                        {{ __('app.dashboard_ui.usage_over_limit') }}
                                    @else
                                        {{ __('app.dashboard_ui.usage_high') }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    {{ __('app.dashboard_ui.usage_help', ['percent' => $attention['usagePercent'] ?? 0]) }}
                                </p>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-brand-indigo">{{ __('app.dashboard_ui.manage') }}</span>
                    </a>
                @endif

                @endcan
            </div>
        </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
            <section class="br-panel overflow-hidden" aria-labelledby="upcoming-title">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h3 id="upcoming-title" class="font-semibold text-slate-950 dark:text-white">{{ __('app.dashboard_ui.upcoming_bookings') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.dashboard_ui.upcoming_description') }}</p>
                    </div>
                    @can('bookings.view')
                        <a href="{{ route('booking.management.index') }}" class="shrink-0 text-sm font-bold text-brand-indigo hover:underline">{{ __('app.dashboard_ui.view_all') }}</a>
                    @endcan
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($upcomingBookings as $booking)
                        @php
                            $bookingStatus = $booking->status->value;
                            $bookingPaymentStatus = $booking->payment_status->value;
                        @endphp

                        <a href="{{ route('booking.management.show', ['tenant' => $tenant->slug, 'booking' => $booking]) }}" class="block px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                            <div class="grid gap-4 lg:grid-cols-[auto_1fr_auto_auto] lg:items-center">
                                <div class="min-w-[96px]">
                                    <p class="text-sm font-bold text-slate-950 dark:text-white">
                                        {{ $booking->starts_at->setTimezone($timezone)->locale(app()->getLocale())->isoFormat('LT') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $booking->starts_at->setTimezone($timezone)->locale(app()->getLocale())->isoFormat('D MMM') }}
                                    </p>
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $booking->customer?->name ?? '—' }}</p>
                                    <p class="mt-1 truncate text-sm text-slate-500">
                                        {{ $localized($booking->service?->name) }}
                                        <span aria-hidden="true">·</span>
                                        {{ $booking->staff?->display_name ?? __('app.dashboard_ui.auto_assigned') }}
                                    </p>
                                    <p class="mt-1 truncate text-xs text-slate-400">{{ $booking->booking_reference }}</p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$bookingStatus] ?? $statusClasses['pending'] }}">
                                        {{ $statusLabels[$bookingStatus] ?? $bookingStatus }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {{ $paymentClasses[$bookingPaymentStatus] ?? $paymentClasses['unpaid'] }}">
                                        {{ $paymentLabels[$bookingPaymentStatus] ?? $bookingPaymentStatus }}
                                    </span>
                                </div>

                                <div class="text-start lg:text-end">
                                    <p class="text-xs font-medium text-slate-400">{{ __('app.dashboard_ui.current_service_price') }}</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                                        {{ $money((int) $booking->service?->price_minor, (string) ($booking->service?->currency ?? 'EGP')) }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-12">
                            <div class="mx-auto max-w-md text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2.5"/><path stroke-linecap="round" d="M8 3v4M16 3v4M4 10h16"/><path stroke-linecap="round" d="M8 14h2M12 14h4M8 17h6"/></svg>
                                </div>
                                <p class="mt-4 font-semibold text-slate-900 dark:text-white">{{ __('app.dashboard_ui.no_upcoming_bookings') }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.dashboard_ui.no_upcoming_help') }}</p>
                                <a href="{{ route('public.booking.canonical.show', ['tenant' => $tenant->slug]) }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('app.dashboard_ui.view_booking_page') }}
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-6">
                @if ($canViewBilling)
                <section class="br-panel p-5" aria-labelledby="subscription-title">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.dashboard_ui.subscription') }}</p>
                            <h3 id="subscription-title" class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ $planName }}</h3>
                        </div>
                        @can('billing.view')
                            <a href="{{ route('billing.subscription') }}" class="text-sm font-bold text-brand-indigo hover:underline">{{ __('app.dashboard_ui.manage') }}</a>
                        @endcan
                    </div>

                    @if ($subscription)
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl br-surface-soft p-3">
                                <p class="text-xs font-semibold text-slate-500">{{ __('app.dashboard_ui.subscription_status') }}</p>
                                @php
                                    $subscriptionStatusKey = $subscription->cancelled_at !== null
                                        ? 'cancellation_scheduled'
                                        : $subscription->status->value;
                                @endphp
                                <p class="mt-1">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {{ $subscriptionStatusClasses[$subscriptionStatusKey] ?? 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                    {{ $subscriptionStatusLabels[$subscriptionStatusKey] ?? $subscription->status->value }}
                                </span>
                            </p>
                            </div>
                            <div class="rounded-xl br-surface-soft p-3">
                                <p class="text-xs font-semibold text-slate-500">{{ __('app.dashboard_ui.renews') }}</p>
                                <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ $subscription->end_at->setTimezone($timezone)->locale(app()->getLocale())->isoFormat('D MMM YYYY') }}</p>
                                @if ($subscriptionDaysRemaining !== null)
                                    <p class="mt-1 text-xs font-semibold text-slate-400">
                                        {{ trans_choice('app.dashboard_ui.subscription_days_left', $subscriptionDaysRemaining, ['count' => $subscriptionDaysRemaining]) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/25 dark:text-amber-200">
                            {{ __('app.dashboard_ui.no_active_subscription') }}
                        </p>
                    @endif
                </section>
                @endif

                @can('billing.view')
                @if ($usageSummary)
                    <section class="br-panel p-5" aria-labelledby="usage-title">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.dashboard_ui.customer_usage') }}</p>
                                <h3 id="usage-title" class="mt-2 text-xl font-bold text-slate-950 dark:text-white">
                                    {{ number_format($usageSummary->uniqueCustomerCount) }} / {{ number_format($usageSummary->includedCustomerLimit) }}
                                </h3>
                            </div>
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $usagePercent }}%</span>
                        </div>

                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                             role="progressbar"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             aria-valuenow="{{ $usagePercent }}"
                             aria-label="{{ __('app.dashboard_ui.usage_help', ['percent' => $usagePercent]) }}">
                            <div class="h-full rounded-full bg-brand-indigo transition-[width] duration-300" style="width: {{ $usagePercent }}%"></div>
                        </div>

                        <p class="mt-3 text-sm text-slate-500">
                            {{ __('app.dashboard_ui.customers_count', ['count' => number_format($usageSummary->uniqueCustomerCount)]) }}
                        </p>

                        @if ($usageSummary->additionalCustomerCount > 0)
                            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm font-medium text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/25 dark:text-amber-200">
                                {{ __('app.dashboard_ui.additional_customers', ['count' => number_format($usageSummary->additionalCustomerCount)]) }}
                            </p>
                        @endif
                    </section>
                @endif
                @endcan

                <section class="br-panel p-5" aria-labelledby="workspace-title">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.dashboard_ui.workspace') }}</p>
                    </div>

                    <div class="mt-4 flex h-28 w-full items-center justify-center overflow-hidden rounded-2xl bg-brand-soft p-3 dark:bg-slate-800">
                        @if ($tenant->profile?->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tenant->profile->logo_path) }}"
                                 alt="{{ $businessName }}"
                                 class="h-full w-full object-contain">
                        @else
                            <img src="{{ asset('logo.png') }}" alt="BookResa" class="h-full w-full object-contain p-2 dark:hidden">
                            <img src="{{ asset('logodark.png') }}" alt="BookResa" class="hidden h-full w-full object-contain p-2 dark:block">
                        @endif
                    </div>

                    <h3 id="workspace-title" class="mt-3 truncate text-lg font-bold text-slate-950 dark:text-white">{{ $businessName }}</h3>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        @can('bookings.view')
                            <a href="{{ route('booking.management.index') }}" class="rounded-xl br-surface-soft px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('app.bookings') }}
                            </a>
                        @endcan
                        @can('calendar.view')
                            <a href="{{ route('calendar.index') }}" class="rounded-xl br-surface-soft px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('app.calendar') }}
                            </a>
                        @endcan
                        @can('customers.view')
                            <a href="{{ route('customers.index') }}" class="rounded-xl br-surface-soft px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('app.customers') }}
                            </a>
                        @endcan
                        @can('business.view')
                            <a href="{{ route('business.profile.edit') }}" class="rounded-xl br-surface-soft px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('app.business') }}
                            </a>
                        @endcan
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection