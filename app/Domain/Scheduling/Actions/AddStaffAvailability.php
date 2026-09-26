<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class AddStaffAvailability
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(StaffProfile $staff, array $data): StaffAvailability
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $staff->tenant_id !== $tenantId) {
            throw new LogicException('Staff must belong to the current tenant.');
        }

        return StaffAvailability::query()->create([
            'tenant_id' => $tenantId,
            'staff_id' => $staff->getKey(),
            'available_date' => $data['available_date'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
        ]);
    }
}
