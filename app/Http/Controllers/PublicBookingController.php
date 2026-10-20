<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Actions\UpdateBookingStatus;
use App\Domain\Booking\Services\WorkspacePaymentMethods;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus as PaymentRecordStatus;
use App\Domain\Payment\Services\StartBookingPayment;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\PublicBookingAvailabilityRequest;
use App\Http\Requests\StorePublicBookingRequest;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use LogicException;
use RuntimeException;
use Throwable;

class PublicBookingController
{
    public function show(Tenant $tenant, CurrentTenant $currentTenant, WorkspacePaymentMethods $workspacePaymentMethods): View
    {
        $this->ensurePublicTenant($tenant);

        return $currentTenant->run($tenant, function () use ($tenant, $workspacePaymentMethods): View {
            $bookingSettings = data_get($tenant->profile, 'booking_settings', []);
            $paymentMethodMode = $workspacePaymentMethods->mode($tenant);
            $availablePaymentMethods = $workspacePaymentMethods->availableMethods($tenant);

            return view('public.booking.show', [
                'tenant' => $tenant->load('profile'),
                'services' => Service::query()
                    ->where('is_active', true)
                    ->with('staff:id,display_name,status')
                    ->orderBy('id')
                    ->get(['id', 'name', 'price_minor', 'currency', 'duration_minutes']),
                'today' => CarbonImmutable::now((string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC')))->toDateString(),
                'paymentMethodMode' => $paymentMethodMode,
                'availablePaymentMethods' => $availablePaymentMethods,
                'cashPaymentsAvailable' => in_array('cash', $availablePaymentMethods, true),
                'onlinePaymentsAvailable' => in_array('online', $availablePaymentMethods, true),
                'customerEmailRequired' => (bool) data_get($bookingSettings, 'customer_email_required', false),
            ]);
        });
    }

    public function availability(
        Tenant $tenant,
        PublicBookingAvailabilityRequest $request,
        CurrentTenant $currentTenant,
        AvailabilityService $availability,
    ): JsonResponse {
        $this->ensurePublicTenant($tenant);

        return $currentTenant->run($tenant, function () use ($request, $availability, $tenant): JsonResponse {
            $service = Service::query()->findOrFail($request->integer('service_id'));
            $hasStaffAssignments = $service->staff()->exists();
            $hasActiveStaffAssignments = $service->staff()->where('status', StaffStatus::Active->value)->exists();
            $staff = $request->filled('staff_id')
                ? StaffProfile::query()->findOrFail($request->integer('staff_id'))
                : null;

            $date = CarbonImmutable::createFromFormat(
                'Y-m-d',
                $request->string('date')->toString(),
                (string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC')),
            );

            try {
                $slots = $availability->slots($service, $date, $staff);
            } catch (LogicException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            $availableSlots = collect($slots)
                ->groupBy(fn (array $slot): string => $slot['start']->format('H:i'))
                ->map(function ($sameTimeSlots) use ($staff): array {
                    $slot = $sameTimeSlots->first();
                    $staffCount = $sameTimeSlots
                        ->pluck('staff_id')
                        ->filter()
                        ->unique()
                        ->count();

                    return [
                        'date' => $slot['start']->toDateString(),
                        'time' => $slot['start']->format('H:i'),
                        'end_time' => $slot['end']->format('H:i'),
                        'staff_id' => $staff?->getKey(),
                        'available_staff_count' => $staffCount > 0 ? $staffCount : null,
                    ];
                })
                ->values();

            $response = ['data' => $availableSlots];

            if ($availableSlots->isEmpty() && $hasStaffAssignments && !$hasActiveStaffAssignments) {
                $response['reason'] = 'no_active_staff';
            }

            return response()->json($response);
        });
    }

    public function store(
        Tenant $tenant,
        StorePublicBookingRequest $request,
        CurrentTenant $currentTenant,
        CreateBooking $createBooking,
        UpdateBookingStatus $updateBookingStatus,
        StartBookingPayment $startBookingPayment,
        WorkspacePaymentMethods $workspacePaymentMethods,
    ): RedirectResponse {
        $this->ensurePublicTenant($tenant);

        try {
            return $currentTenant->run($tenant, function () use ($request, $createBooking, $updateBookingStatus, $startBookingPayment, $tenant, $workspacePaymentMethods): RedirectResponse {
                $service = Service::query()->findOrFail($request->integer('service_id'));
                $staff = $request->filled('staff_id')
                    ? StaffProfile::query()->findOrFail($request->integer('staff_id'))
                    : null;

                if ($service->staff()->exists() && $staff === null) {
                    throw new RuntimeException(__('app.public_booking_ui.select_staff_required'));
                }

                $paymentMethod = (string) $request->input('payment_method', 'cash');

                if (! $workspacePaymentMethods->allows($tenant, $paymentMethod)) {
                    $message = $paymentMethod === 'online'
                        ? __('app.public_booking_ui.online_payment_unavailable')
                        : __('app.public_booking_ui.payment_method_unavailable');

                    throw new RuntimeException($message);
                }

                $timezone = (string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC'));
                $startsAt = CarbonImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $request->string('date').' '.$request->string('time'),
                    $timezone,
                );

                $booking = $createBooking->handle(
                    $service,
                    $request->string('name')->toString(),
                    $request->filled('phone') ? $request->string('phone')->toString() : null,
                    $request->filled('email') ? $request->string('email')->toString() : null,
                    $startsAt,
                    $staff,
                    $request->filled('notes') ? $request->string('notes')->toString() : null,
                    paymentMethod: $paymentMethod,
                );

                if ($paymentMethod === 'online') {
                    try {
                        $payment = $startBookingPayment->handle($booking);
                    } catch (Throwable $exception) {
                        $this->cancelBookingWhenCheckoutWasNotCreated($booking, $updateBookingStatus);

                        if ($exception instanceof RuntimeException || $exception instanceof LogicException) {
                            throw $exception;
                        }

                        report($exception);

                        throw new RuntimeException('Payment checkout could not be started.', previous: $exception);
                    }

                    if (blank($payment->checkout_url)) {
                        $this->cancelBookingWhenCheckoutWasNotCreated($booking, $updateBookingStatus);

                        throw new RuntimeException('Payment checkout could not be started.');
                    }

                    return redirect()->away($payment->checkout_url);
                }

                return redirect()->to(URL::signedRoute('public.booking.canonical.confirmation', [
                    'tenant' => $tenant->slug,
                    'booking' => $booking->booking_reference,
                ]));
            });
        } catch (RuntimeException|LogicException $exception) {
            return back()
                ->withErrors(['booking' => $exception->getMessage()])
                ->withInput();
        }
    }

    public function confirmation(
        Tenant $tenant,
        string $booking,
        CurrentTenant $currentTenant,
    ): View {
        $this->ensurePublicTenant($tenant);

        return $currentTenant->run($tenant, function () use ($booking, $tenant): View {
            $model = Booking::query()
                ->where('booking_reference', $booking)
                ->with(['customer', 'service', 'staff', 'payments', 'statusHistory'])
                ->firstOrFail();

            return view('public.booking.confirmation', [
                'booking' => $model,
                'tenant' => $tenant,
            ]);
        });
    }

    public function retryPayment(
        Tenant $tenant,
        string $booking,
        CurrentTenant $currentTenant,
        StartBookingPayment $startBookingPayment,
        UpdateBookingStatus $updateBookingStatus,
        AvailabilityService $availability,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $this->ensurePublicTenant($tenant);

        $confirmationUrl = URL::temporarySignedRoute(
            'public.booking.canonical.confirmation',
            now()->addDays(7),
            ['tenant' => $tenant->slug, 'booking' => $booking],
        );

        try {
            return $currentTenant->run($tenant, function () use (
                $booking,
                $tenant,
                $currentTenant,
                $startBookingPayment,
                $updateBookingStatus,
                $availability,
                $auditLogger,
                $confirmationUrl,
            ): RedirectResponse {
                $model = Booking::query()
                    ->where('booking_reference', $booking)
                    ->with(['customer', 'service', 'staff', 'payments', 'statusHistory'])
                    ->firstOrFail();

                if ($model->payment_method !== 'online'
                    || $model->starts_at->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
                    throw new RuntimeException(__('app.payment_retry_unavailable'));
                }

                if ($model->payments()->where('status', PaymentRecordStatus::Paid->value)->exists()) {
                    return redirect()->to($confirmationUrl)
                        ->with('payment_notice', __('app.payment_already_completed'));
                }

                if ($model->payments()->where('status', PaymentRecordStatus::Refunded->value)->exists()) {
                    throw new RuntimeException(__('app.payment_retry_unavailable'));
                }

                if ($model->status === BookingStatus::Cancelled) {
                    $model = $this->reopenCancelledBookingForPaymentRetry(
                        $model,
                        $tenant,
                        $currentTenant,
                        $availability,
                        $auditLogger,
                    );
                } elseif ($model->status !== BookingStatus::Pending) {
                    throw new RuntimeException(__('app.payment_retry_unavailable'));
                }

                $latestPayment = $model->payments()->latest('id')->first();

                if (
                    $latestPayment !== null
                    && in_array($latestPayment->status, [
                        PaymentRecordStatus::Pending,
                        PaymentRecordStatus::Processing,
                    ], true)
                    && filled($latestPayment->checkout_url)
                    && ($latestPayment->expires_at === null || $latestPayment->expires_at->isFuture())
                ) {
                    return redirect()->away($latestPayment->checkout_url);
                }

                try {
                    $payment = $startBookingPayment->handle($model->fresh(['customer', 'service']));
                } catch (Throwable $exception) {
                    $this->cancelBookingWhenCheckoutWasNotCreated($model, $updateBookingStatus);

                    if ($exception instanceof RuntimeException || $exception instanceof LogicException) {
                        throw $exception;
                    }

                    report($exception);
                    throw new RuntimeException('Payment checkout could not be started.', previous: $exception);
                }

                if (blank($payment->checkout_url)) {
                    $this->cancelBookingWhenCheckoutWasNotCreated($model, $updateBookingStatus);
                    throw new RuntimeException('Payment checkout could not be started.');
                }

                return redirect()->away($payment->checkout_url);
            });
        } catch (RuntimeException|LogicException $exception) {
            return redirect()->to($confirmationUrl)
                ->withErrors(['payment_retry' => $exception->getMessage()]);
        }
    }

    private function reopenCancelledBookingForPaymentRetry(
        Booking $booking,
        Tenant $tenant,
        CurrentTenant $currentTenant,
        AvailabilityService $availability,
        AuditLogger $auditLogger,
    ): Booking {
        return DB::transaction(function () use (
            $booking,
            $tenant,
            $currentTenant,
            $availability,
            $auditLogger,
        ): Booking {
            $locked = Booking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === BookingStatus::Pending) {
                return $locked->fresh(['customer', 'service', 'staff', 'payments', 'statusHistory']);
            }

            if ($locked->status !== BookingStatus::Cancelled) {
                throw new RuntimeException(__('app.payment_retry_unavailable'));
            }

            $lastStatus = $locked->statusHistory()->latest('id')->first();

            if (!in_array(
                $lastStatus?->reason,
                Booking::RETRYABLE_ONLINE_PAYMENT_CANCELLATION_REASONS,
                true,
            )) {
                throw new RuntimeException(__('app.payment_retry_unavailable'));
            }

            if ($locked->starts_at->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
                throw new RuntimeException(__('app.payment_retry_unavailable'));
            }

            $locked->loadMissing(['service', 'staff']);

            if ($locked->service === null
                || !$locked->service->is_active
                || ($locked->staff_id === null && $locked->service->staff()->exists())) {
                throw new RuntimeException(__('app.payment_retry_slot_unavailable'));
            }

            $timezone = (string) data_get(
                $currentTenant->get()?->profile,
                'timezone',
                config('app.timezone', 'UTC'),
            );
            $localStart = $locked->starts_at->setTimezone($timezone);
            $scope = $locked->staff_id === null
                ? sprintf('%d:business:%s', $tenant->getKey(), $localStart->toDateString())
                : sprintf('%d:staff:%d:%s', $tenant->getKey(), $locked->staff_id, $localStart->toDateString());

            DB::table('booking_locks')->insertOrIgnore([
                'tenant_id' => $tenant->getKey(),
                'scope_key' => $scope,
                'lock_date' => $localStart->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('booking_locks')
                ->where('scope_key', $scope)
                ->lockForUpdate()
                ->first();

            if (!$availability->isAvailable($locked->service, CarbonImmutable::instance($locked->starts_at), $locked->staff)) {
                throw new RuntimeException(__('app.payment_retry_slot_unavailable'));
            }

            $locked->forceFill(['status' => BookingStatus::Pending])->save();
            $locked->statusHistory()->create([
                'from_status' => BookingStatus::Cancelled->value,
                'to_status' => BookingStatus::Pending->value,
                'changed_by' => auth()->id(),
                'reason' => Booking::PAYMENT_RETRY_REOPENED_REASON,
            ]);

            $fresh = $locked->fresh(['customer', 'service', 'staff', 'payments', 'statusHistory']);

            $auditLogger->log('booking.payment_retry_reopened', $fresh, [
                'tenant_id' => (int) $fresh->tenant_id,
                'booking_id' => (int) $fresh->getKey(),
                'booking_reference' => $fresh->booking_reference,
            ]);

            return $fresh;
        }, 3);
    }

    private function cancelBookingWhenCheckoutWasNotCreated(
        Booking $booking,
        UpdateBookingStatus $updateBookingStatus,
    ): void {
        if ($booking->status !== BookingStatus::Pending) {
            return;
        }

        $latestPayment = $booking->payments()->latest('id')->first();

        $hasPotentialSession = $latestPayment !== null && (
            filled($latestPayment->provider_reference)
            || filled($latestPayment->checkout_url)
            || in_array($latestPayment->status, [
                PaymentRecordStatus::Processing,
                PaymentRecordStatus::Paid,
            ], true)
        );

        if ($hasPotentialSession) {
            return;
        }

        $updateBookingStatus->handle(
            $booking,
            BookingStatus::Cancelled,
            Booking::CHECKOUT_CREATION_FAILED_CANCELLATION_REASON,
        );
    }

    private function ensurePublicTenant(Tenant $tenant): void
    {
        abort_unless(
            $tenant->status === TenantStatus::Active,
            404,
        );
    }
}
