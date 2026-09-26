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
        $holidayDate = (string) $data['holiday_date'];

        $holiday = BusinessHoliday::query()
            ->whereDate('holiday_date', $holidayDate)
            ->first();

        if ($holiday !== null) {
            $holiday->fill([
                'reason' => $data['reason'] ?? null,
            ])->save();

            return $holiday->fresh();
        }

        return BusinessHoliday::query()->create([
            'tenant_id' => $tenantId,
            'holiday_date' => $holidayDate,
            'reason' => $data['reason'] ?? null,
        ]);
    }
}
