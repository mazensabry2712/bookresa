<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class RemoveStaffDayOff
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(StaffDayOff $dayOff): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $dayOff->tenant_id !== $tenantId) {
            throw new LogicException('Staff day off must belong to the current tenant.');
        }

        $dayOff->delete();
    }
}
