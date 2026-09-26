<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Payment\Services\StartBookingPayment;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\PublicBookingAvailabilityRequest;
use App\Http\Requests\StorePublicBookingRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use LogicException;
use RuntimeException;

class PublicBookingController
{
    public function show(Tenant $tenant, CurrentTenant $currentTenant): View
    {
        $this->ensurePublicTenant($tenant);

        return $currentTenant->run($tenant, function () use ($tenant): View {
            return view('public.booking.show', [
                'tenant' => $tenant->load('profile'),
                'services' => Service::query()
                    ->where('is_active', true)
                    ->with('staff:id,display_name,status')
                    ->orderBy('id')
                    ->get(['id', 'name', 'price_minor', 'currency', 'duration_minutes']),
                'today' => CarbonImmutable::now((string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC')))->toDateString(),
                'paymentMode' => (string) data_get(data_get($tenant->profile, 'booking_settings', []), 'payment_mode', 'pay_later'),
                'depositPercent' => (int) data_get(data_get($tenant->profile, 'booking_settings', []), 'deposit_percent', 50),
                'customerEmailRequired' => (bool) data_get(data_get($tenant->profile, 'booking_settings', []), 'customer_email_required', false),
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

            return response()->json([
                'data' => collect($slots)->map(fn (array $slot): array => [
                    'date' => $slot['start']->toDateString(),
                    'time' => $slot['start']->format('H:i'),
                    'end_time' => $slot['end']->format('H:i'),
                    'staff_id' => $slot['staff_id'],
                ])->values(),
            ]);
        });
    }

    public function store(
        Tenant $tenant,
        StorePublicBookingRequest $request,
        CurrentTenant $currentTenant,
        CreateBooking $createBooking,
        StartBookingPayment $startBookingPayment,
    ): RedirectResponse {
        $this->ensurePublicTenant($tenant);

        try {
            return $currentTenant->run($tenant, function () use ($request, $createBooking, $startBookingPayment, $tenant): RedirectResponse {
                $service = Service::query()->findOrFail($request->integer('service_id'));
                $staff = $request->filled('staff_id')
                    ? StaffProfile::query()->findOrFail($request->integer('staff_id'))
                    : null;

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

                $bookingSettings = data_get($tenant->profile, 'booking_settings', []);
                $paymentMode = data_get($bookingSettings, 'payment_mode');

                $paymentRequired = $paymentMode !== null
                    ? in_array($paymentMode, ['full', 'deposit'], true)
                    : (bool) data_get($bookingSettings, 'payment_required', false);

                if ($paymentRequired) {
                    $payment = $startBookingPayment->handle($booking);

                    if ($payment->checkout_url === null) {
                        throw new RuntimeException('Payment checkout could not be started.');
                    }

                    return redirect()->away($payment->checkout_url);
                }

                return redirect()->to(URL::signedRoute('public.booking.confirmation', [
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
            $model = \App\Domain\Booking\Models\Booking::query()
                ->where('booking_reference', $booking)
                ->with(['customer', 'service', 'staff', 'payments'])
                ->firstOrFail();

            return view('public.booking.confirmation', [
                'booking' => $model,
                'tenant' => $tenant,
            ]);
        });
    }

    private function ensurePublicTenant(Tenant $tenant): void
    {
        abort_unless(
            $tenant->status === TenantStatus::Active,
            404,
        );
    }
}
