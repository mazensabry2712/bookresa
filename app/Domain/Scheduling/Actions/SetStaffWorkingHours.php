<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use LogicException;

final class SetStaffWorkingHours
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(StaffProfile $staff, array $hours): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $staff->tenant_id !== $tenantId) {
            throw new LogicException('Staff must belong to the current tenant.');
        }

        DB::transaction(function () use ($staff, $hours, $tenantId): void {
            foreach ($hours as $item) {
                $day = DayOfWeek::from((int) $item['day_of_week']);
                $closed = (bool) ($item['is_closed'] ?? false);

                if (! $closed && (
                    ! isset($item['opens_at'], $item['closes_at'])
                    || (string) $item['closes_at'] <= (string) $item['opens_at']
                )) {
                    throw new \InvalidArgumentException('Staff working hours must have a valid opening window.');
                }

                StaffWorkingHour::query()->updateOrCreate(
                    [
                        'staff_id' => $staff->getKey(),
                        'day_of_week' => $day->value,
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'opens_at' => $item['opens_at'] ?? '00:00:00',
                        'closes_at' => $item['closes_at'] ?? '00:00:00',
                        'is_closed' => $closed,
                    ],
                );
            }
        });
    }
}
