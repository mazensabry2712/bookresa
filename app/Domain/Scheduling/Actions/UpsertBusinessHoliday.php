<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Tenant\Services\CurrentTenant;

final class UpsertBusinessHoliday
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(array $data): BusinessHoliday
    {
        $tenantId = $this->currentTenant->idOrFail();

        return BusinessHoliday::query()->updateOrCreate(
            ['holiday_date' => $data['holiday_date']],
            [
                'tenant_id' => $tenantId,
                'reason' => $data['reason'] ?? null,
            ],
        );
    }
}
