<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use LogicException;

final class AvailabilityService
{
    public const SLOT_INTERVAL_MINUTES = 15;

    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    /**
     * @return array<int, array{start:CarbonImmutable,end:CarbonImmutable,staff_id:?int}>
     */
    public function slots(
        Service $service,
        CarbonImmutable $date,
        ?StaffProfile $requestedStaff = null,
    ): array {
        $tenantId = $this->currentTenant->idOrFail();

        if (! $service->is_active) {
            return [];
        }

        if ((int) $service->tenant_id !== $tenantId) {
            throw new LogicException('Service must belong to the current tenant.');
        }

        $timezone = $this->timezone();
        $localDate = $date->setTimezone($timezone)->startOfDay();

        if ($localDate->isBefore(CarbonImmutable::now($timezone)->startOfDay())) {
            return [];
        }

        $businessWindows = $this->businessWindows($tenantId, $localDate);

        if ($businessWindows->isEmpty()) {
            return [];
        }

        $assignedStaff = $service->staff()
            ->where('status', StaffStatus::Active->value)
            ->orderBy('staff_profiles.id')
            ->get();

        if ($requestedStaff !== null) {
            if ((int) $requestedStaff->tenant_id !== $tenantId) {
                throw new LogicException('Staff must belong to the current tenant.');
            }

            $assigned = $assignedStaff->contains(fn (StaffProfile $staff): bool => $staff->is($requestedStaff));

            if (! $assigned && $assignedStaff->isNotEmpty()) {
                throw new LogicException('Selected staff member is not assigned to this service.');
            }

            return $this->staffSlots($service, $requestedStaff, $localDate, $businessWindows);
        }

        if ($assignedStaff->isEmpty()) {
            return $this->generateSlots($service, $businessWindows, $localDate, null);
        }

        $slots = [];

        foreach ($assignedStaff as $staff) {
            $slots = [...$slots, ...$this->staffSlots($service, $staff, $localDate, $businessWindows)];
        }

        return $slots;
    }

    public function isAvailable(
        Service $service,
        CarbonImmutable $startsAt,
        ?StaffProfile $staff = null,
    ): bool {
        $timezone = $this->timezone();
        $localStart = $startsAt->setTimezone($timezone);

        foreach ($this->slots($service, $localStart->startOfDay(), $staff) as $slot) {
            if ($slot['start']->equalTo($localStart)) {
                return true;
            }
        }

        return false;
    }

    private function staffSlots(
        Service $service,
        StaffProfile $staff,
        CarbonImmutable $date,
        Collection $businessWindows,
    ): array {
        if ((int) $staff->tenant_id !== $this->currentTenant->idOrFail()) {
            throw new LogicException('Staff must belong to the current tenant.');
        }

        $dayOff = StaffDayOff::query()
            ->where('staff_id', $staff->getKey())
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->exists();

        if ($dayOff) {
            return [];
        }

        $staffWindows = StaffAvailability::query()
            ->where('staff_id', $staff->getKey())
            ->whereDate('available_date', $date->toDateString())
            ->orderBy('starts_at')
            ->get(['starts_at', 'ends_at']);

        if ($staffWindows->isEmpty()) {
            $staffWindows = StaffWorkingHour::query()
                ->where('staff_id', $staff->getKey())
                ->where('day_of_week', $date->dayOfWeekIso)
                ->where('is_closed', false)
                ->get(['opens_at', 'closes_at']);

            if ($staffWindows->isEmpty()) {
                return $this->generateSlots($service, $businessWindows, $date, $staff);
            }

            $staffWindows = $staffWindows->map(fn (StaffWorkingHour $window): array => [
                'start' => $window->opens_at,
                'end' => $window->closes_at,
            ]);
        } else {
            $staffWindows = $staffWindows->map(fn (StaffAvailability $window): array => [
                'start' => $window->starts_at,
                'end' => $window->ends_at,
            ]);
        }

        $intersection = $this->intersectWindows($businessWindows, $staffWindows);
        return $this->generateSlots($service, $intersection, $date, $staff);
    }

    private function businessWindows(int $tenantId, CarbonImmutable $date): Collection
    {
        if (BusinessHoliday::query()->whereDate('holiday_date', $date->toDateString())->exists()) {
            return collect();
        }

        $special = SpecialWorkingHour::query()
            ->whereDate('work_date', $date->toDateString())
            ->first();

        if ($special !== null) {
            if ($special->is_closed || $special->opens_at === null || $special->closes_at === null) {
                return collect();
            }

            return collect([[
                'start' => $special->opens_at,
                'end' => $special->closes_at,
            ]])->pipe(fn (Collection $windows) => $this->subtractBusinessBreaks($windows, $date));
        }

        $hours = BusinessWorkingHour::query()
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('is_closed', false)
            ->get(['opens_at', 'closes_at']);

        if ($hours->isEmpty()) {
            return collect();
        }

        $windows = $hours->map(fn (BusinessWorkingHour $hour): array => [
            'start' => $hour->opens_at,
            'end' => $hour->closes_at,
        ]);

        return $this->subtractBusinessBreaks($windows, $date);
    }

    private function subtractBusinessBreaks(Collection $windows, CarbonImmutable $date): Collection
    {
        $breaks = BusinessBreak::query()
            ->where('day_of_week', $date->dayOfWeekIso)
            ->orderBy('starts_at')
            ->get(['starts_at', 'ends_at']);

        foreach ($breaks as $break) {
            $result = collect();

            foreach ($windows as $window) {
                $start = $this->atTime($date, $window['start']);
                $end = $this->atTime($date, $window['end']);
                $breakStart = $this->atTime($date, $break->starts_at);
                $breakEnd = $this->atTime($date, $break->ends_at);

                if ($breakEnd->lessThanOrEqualTo($start) || $breakStart->greaterThanOrEqualTo($end)) {
                    $result->push($window);
                    continue;
                }

                if ($breakStart->greaterThan($start)) {
                    $result->push([
                        'start' => $start->format('H:i:s'),
                        'end' => $breakStart->format('H:i:s'),
                    ]);
                }

                if ($breakEnd->lessThan($end)) {
                    $result->push([
                        'start' => $breakEnd->format('H:i:s'),
                        'end' => $end->format('H:i:s'),
                    ]);
                }
            }

            $windows = $result;
        }

        return $windows;
    }

    private function intersectWindows(Collection $businessWindows, Collection $staffWindows): Collection
    {
        $result = collect();

        foreach ($businessWindows as $business) {
            $businessStart = $this->timeToMinutes($business['start']);
            $businessEnd = $this->timeToMinutes($business['end']);

            foreach ($staffWindows as $staff) {
                $staffStart = $this->timeToMinutes($staff['start']);
                $staffEnd = $this->timeToMinutes($staff['end']);

                $start = max($businessStart, $staffStart);
                $end = min($businessEnd, $staffEnd);

                if ($start < $end) {
                    $result->push([
                        'start' => sprintf('%02d:%02d:00', intdiv($start, 60), $start % 60),
                        'end' => sprintf('%02d:%02d:00', intdiv($end, 60), $end % 60),
                    ]);
                }
            }
        }

        return $result;
    }

    private function generateSlots(
        Service $service,
        Collection $windows,
        CarbonImmutable $date,
        ?StaffProfile $staff,
    ): array {
        $duration = (int) $service->duration_minutes;
        $buffer = (int) $service->buffer_minutes;
        $slots = [];

        $dateStart = $date->startOfDay();
        $dateEnd = $dateStart->addDay();

        $bookings = Booking::query()
            ->where('service_id', $service->getKey())
            ->when(
                $staff !== null,
                fn ($query) => $query->where('staff_id', $staff->getKey()),
                fn ($query) => $query->whereNull('staff_id'),
            )
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Rescheduled->value,
            ])
            ->where('starts_at', '<', $dateEnd->setTimezone('UTC'))
            ->where('block_ends_at', '>', $dateStart->setTimezone('UTC'))
            ->get(['starts_at', 'block_ends_at']);

        foreach ($windows as $window) {
            $windowStart = $this->atTime($date, $window['start']);
            $windowEnd = $this->atTime($date, $window['end']);

            $cursor = $windowStart;
            $latestStart = $windowEnd->subMinutes($duration + $buffer);

            while ($cursor->lessThanOrEqualTo($latestStart)) {
                $slotEnd = $cursor->addMinutes($duration);
                $blockEnd = $slotEnd->addMinutes($buffer);

                $overlaps = $bookings->contains(
                    fn (Booking $booking): bool =>
                        $booking->starts_at->lessThan($blockEnd->setTimezone('UTC'))
                        && $booking->block_ends_at->greaterThan($cursor->setTimezone('UTC')),
                );

                if (! $overlaps) {
                    $slots[] = [
                        'start' => $cursor,
                        'end' => $slotEnd,
                        'staff_id' => $staff?->getKey(),
                    ];
                }

                $cursor = $cursor->addMinutes((int) config('bookresa.booking.slot_interval_minutes', self::SLOT_INTERVAL_MINUTES));
            }
        }

        return $slots;
    }

    private function timezone(): string
    {
        return $this->currentTenant->get()?->profile?->timezone
            ?? config('app.timezone', 'UTC');
    }

    private function atTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $date->setTime($hour, $minute);
    }

    private function timeToMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hour * 60) + $minute;
    }
}
