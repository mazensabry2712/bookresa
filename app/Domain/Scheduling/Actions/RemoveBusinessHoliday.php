<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class RemoveBusinessHoliday
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(BusinessHoliday $holiday): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $holiday->tenant_id !== $tenantId) {
            throw new LogicException('Business holiday must belong to the current tenant.');
        }

        $holiday->delete();
    }
}
