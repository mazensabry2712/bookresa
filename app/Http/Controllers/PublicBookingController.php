<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Actions\UpdateBookingStatus;
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
use App\Support\PlatformSettings;
use App\Http\Requests\PublicBookingAvailabilityRequest;
use App\Http\Requests\StorePublicBookingRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use LogicException;
use RuntimeException;
use Throwable;

class PublicBookingController
{
    public function show(Tenant $tenant, CurrentTenant $currentTenant, PlatformSettings $platformSettings): View
    {
        $this->ensurePublicTenant($tenant);

        return $currentTenant->run($tenant, function () use ($tenant, $platformSettings): View {
            $bookingSettings = data_get($tenant->profile, 'booking_settings', []);
            $centralizedPaymentsEnabled = filter_var(
                $platformSettings->get('centralized_booking_payments_enabled', false),
                FILTER_VALIDATE_BOOL,
            );
            $paymentMode = $centralizedPaymentsEnabled ? 'full' : 'pay_later';

            return view('public.booking.show', [
                'tenant' => $tenant->load('profile'),
                'services' => Service::query()
                    ->where('is_active', true)
                    ->with('staff:id,display_name,status')
                    ->orderBy('id')
                    ->get(['id', 'name', 'price_minor', 'currency', 'duration_minutes']),
                'today' => CarbonImmutable::now((string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC')))->toDateString(),
                'paymentMode' => $paymentMode,
                'depositPercent' => 0,
                'customerEmailRequired' => $centralizedPaymentsEnabled
                    || (bool) data_get(data_get($tenant->profile, 'booking_settings', []), 'customer_email_required', false),
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

            return response()->json([
                'data' => $availableSlots,
                'reason' => $availableSlots->isEmpty() && $hasStaffAssignments && ! $hasActiveStaffAssignments
                    ? 'no_active_staff'
                    : null,
            ]);
        });
    }

    public function store(
        Tenant $tenant,
        StorePublicBookingRequest $request,
        CurrentTenant $currentTenant,
        CreateBooking $createBooking,
        UpdateBookingStatus $updateBookingStatus,
        StartBookingPayment $startBookingPayment,
        PlatformSettings $platformSettings,
    ): RedirectResponse {
        $this->ensurePublicTenant($tenant);

        try {
            return $currentTenant->run($tenant, function () use ($request, $createBooking, $updateBookingStatus, $startBookingPayment, $tenant, $platformSettings): RedirectResponse {
                $service = Service::query()->findOrFail($request->integer('service_id'));
                $staff = $request->filled('staff_id')
                    ? StaffProfile::query()->findOrFail($request->integer('staff_id'))
                    : null;

                $paymentRequired = filter_var(
                    $platformSettings->get('centralized_booking_payments_enabled', false),
                    FILTER_VALIDATE_BOOL,
                );

                if ($paymentRequired && $request->filled('email') === false) {
                    throw new RuntimeException('Customer email is required for online payment.');
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
                );

                if ($paymentRequired) {
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
                ->with(['customer', 'service', 'staff', 'payments'])
                ->firstOrFail();

            return view('public.booking.confirmation', [
                'booking' => $model,
                'tenant' => $tenant,
            ]);
        });
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
            'Online payment checkout could not be created.',
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
