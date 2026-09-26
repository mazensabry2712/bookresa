<?php

namespace AppDomainBookingServices;

use AppDomainBookingEnumsBookingStatus;
use AppDomainBookingModelsBooking;
use AppDomainSchedulingServicesAvailabilityService;
use AppDomainStaffModelsStaffProfile;
use AppDomainTenantServicesCurrentTenant;
use CarbonCarbonImmutable;
use IlluminateSupportFacadesDB;
use LogicException;
use RuntimeException;

final class RescheduleBooking
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly AvailabilityService $availability,
    ) {
    }

    public function handle(
        Booking $booking,
        CarbonImmutable $startsAt,
        ?StaffProfile $staff = null,
    ): Booking {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking must belong to the current tenant.');
        }

        if (! in_array($booking->status->value, ['pending', 'confirmed', 'rescheduled'], true)) {
            throw new RuntimeException('This booking cannot be rescheduled.');
        }

        $booking->loadMissing('service');

        if ($booking->service === null) {
            throw new RuntimeException('Booking service was not found.');
        }

        $timezone = (string) data_get(
            $this->currentTenant->get()?->profile,
            'timezone',
            config('app.timezone', 'UTC'),
        );
        $localStart = $startsAt->setTimezone($timezone);

        if ($localStart->lessThanOrEqualTo(CarbonImmutable::now($timezone))) {
            throw new RuntimeException('Bookings must be scheduled in the future.');
        }

        $targetStaff = $staff ?? $booking->staff()->first();

        if ($targetStaff !== null && (int) $targetStaff->tenant_id !== $tenantId) {
            throw new LogicException('Staff must belong to the current tenant.');
        }

        return DB::transaction(function () use ($booking, $localStart, $targetStaff, $tenantId): Booking {
            $locked = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            $scope = $targetStaff === null
                ? sprintf('%d:business:%s', $tenantId, $localStart->toDateString())
                : sprintf('%d:staff:%d:%s', $tenantId, $targetStaff->getKey(), $localStart->toDateString());

            DB::table('booking_locks')->insertOrIgnore([
                'tenant_id' => $tenantId,
                'scope_key' => $scope,
                'lock_date' => $localStart->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('booking_locks')->where('scope_key', $scope)->lockForUpdate()->first();

            // Temporarily remove the current booking from availability calculations.
            $locked->forceFill(['status' => BookingStatus::Cancelled])->save();

            if (! $this->availability->isAvailable($locked->service, $localStart, $targetStaff)) {
                throw new RuntimeException('The selected time is no longer available.');
            }

            $duration = (int) $locked->service->duration_minutes;
            $buffer = (int) $locked->service->buffer_minutes;
            $localEnd = $localStart->addMinutes($duration);

            $locked->forceFill([
                'staff_id' => $targetStaff?->getKey(),
                'starts_at' => $localStart->utc(),
                'ends_at' => $localEnd->utc(),
                'block_ends_at' => $localEnd->addMinutes($buffer)->utc(),
                'status' => BookingStatus::Rescheduled,
            ])->save();

            $locked->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => BookingStatus::Rescheduled->value,
                'changed_by' => auth()->id(),
                'reason' => 'Booking rescheduled.',
            ]);

            $fresh = $locked->fresh(['customer', 'service', 'staff', 'statusHistory']);

            $fresh?->customer?->notify(new AppNotificationsBookingNotification($fresh, 'rescheduled'));

            return $fresh;
        }, 3);
    }
}
