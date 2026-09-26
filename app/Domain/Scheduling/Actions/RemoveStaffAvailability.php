<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class RemoveStaffAvailability
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(StaffAvailability $availability): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $availability->tenant_id !== $tenantId) {
            throw new LogicException('Staff availability must belong to the current tenant.');
        }

        $availability->delete();
    }
}
