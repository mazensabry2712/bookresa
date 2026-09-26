<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class AddStaffDayOff
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(StaffProfile $staff, array $data): StaffDayOff
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $staff->tenant_id !== $tenantId) {
            throw new LogicException('Staff must belong to the current tenant.');
        }

        return StaffDayOff::query()->create([
            'tenant_id' => $tenantId,
            'staff_id' => $staff->getKey(),
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'reason' => $data['reason'] ?? null,
        ]);
    }
}
