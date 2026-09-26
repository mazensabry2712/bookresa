<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Notifications\BookingNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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
        ?StaffProfile $requestedStaff = null,
    ): Booking {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking must belong to the current tenant.');
        }

        if (! in_array($booking->status->value, [
            BookingStatus::Pending->value,
            BookingStatus::Confirmed->value,
            BookingStatus::Rescheduled->value,
        ], true)) {
            throw new RuntimeException('This booking cannot be rescheduled.');
        }

        $booking->loadMissing(['service', 'staff']);

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

        if ($requestedStaff !== null) {
            if ((int) $requestedStaff->tenant_id !== $tenantId) {
                throw new LogicException('Staff must belong to the current tenant.');
            }

            if ($requestedStaff->status !== StaffStatus::Active) {
                throw new RuntimeException('Selected staff member is inactive.');
            }

            $assigned = $booking->service->staff()
                ->whereKey($requestedStaff->getKey())
                ->exists();

            if (! $assigned) {
                throw new RuntimeException('Selected staff member is not assigned to this service.');
            }
        }

        return DB::transaction(function () use ($booking, $localStart, $requestedStaff, $tenantId): Booking {
            $locked = Booking::query()
                ->with('service')
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $from = $locked->status;
            $assignedStaff = $locked->service->staff()
                ->where('status', StaffStatus::Active->value)
                ->orderBy('staff_profiles.id')
                ->get();

            /** @var Collection<int, StaffProfile|null> $candidates */
            if ($requestedStaff !== null) {
                $candidates = collect([$requestedStaff]);
            } elseif ($locked->staff !== null && $locked->staff->status === StaffStatus::Active) {
                $candidates = collect([$locked->staff]);
            } elseif ($assignedStaff->isNotEmpty()) {
                $candidates = $assignedStaff;
            } else {
                $candidates = collect([null]);
            }

            $originalStatus = $locked->status;
            $locked->forceFill(['status' => BookingStatus::Cancelled])->save();

            $selectedStaff = null;

            foreach ($candidates as $candidate) {
                $scope = $candidate === null
                    ? sprintf('%d:business:%s', $tenantId, $localStart->toDateString())
                    : sprintf('%d:staff:%d:%s', $tenantId, $candidate->getKey(), $localStart->toDateString());

                DB::table('booking_locks')->insertOrIgnore([
                    'tenant_id' => $tenantId,
                    'scope_key' => $scope,
                    'lock_date' => $localStart->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('booking_locks')
                    ->where('scope_key', $scope)
                    ->lockForUpdate()
                    ->first();

                if ($this->availability->isAvailable($locked->service, $localStart, $candidate)) {
                    $selectedStaff = $candidate;
                    break;
                }
            }

            if ($selectedStaff === null && $assignedStaff->isNotEmpty()) {
                $locked->forceFill(['status' => $originalStatus])->save();

                throw new RuntimeException('No staff member is available at the selected time.');
            }

            if (
                $selectedStaff === null
                && $assignedStaff->isEmpty()
                && ! $this->availability->isAvailable($locked->service, $localStart, null)
            ) {
                $locked->forceFill(['status' => $originalStatus])->save();

                throw new RuntimeException('The selected time is no longer available.');
            }

            $duration = (int) $locked->service->duration_minutes;
            $buffer = (int) $locked->service->buffer_minutes;
            $localEnd = $localStart->addMinutes($duration);

            $locked->forceFill([
                'staff_id' => $selectedStaff?->getKey(),
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

            $fresh?->customer?->notify(new BookingNotification($fresh, 'rescheduled'));

            return $fresh;
        }, 3);
    }
}
