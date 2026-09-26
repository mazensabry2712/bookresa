<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class RemoveSpecialWorkingHour
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(SpecialWorkingHour $specialWorkingHour): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $specialWorkingHour->tenant_id !== $tenantId) {
            throw new LogicException('Special working hour must belong to the current tenant.');
        }

        $specialWorkingHour->delete();
    }
}
