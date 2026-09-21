<?php

namespace App\Domain\Service\Actions;

use App\Domain\Scheduling\Models\ServiceStaff;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class AssignServiceToStaff
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Service $service, StaffProfile $staff): ServiceStaff
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $service->tenant_id !== $tenantId || (int) $staff->tenant_id !== $tenantId) {
            throw new LogicException('Service and staff must belong to the current tenant.');
        }

        return ServiceStaff::query()->firstOrCreate([
            'tenant_id' => $tenantId,
            'service_id' => $service->getKey(),
            'staff_id' => $staff->getKey(),
        ]);
    }
}
