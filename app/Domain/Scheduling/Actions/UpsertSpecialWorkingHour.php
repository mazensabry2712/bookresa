<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Tenant\Services\CurrentTenant;

final class UpsertSpecialWorkingHour
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(array $data): SpecialWorkingHour
    {
        $tenantId = $this->currentTenant->idOrFail();
        $closed = (bool) ($data['is_closed'] ?? false);

        return SpecialWorkingHour::query()->updateOrCreate(
            ['work_date' => $data['work_date']],
            [
                'tenant_id' => $tenantId,
                'opens_at' => $closed ? null : ($data['opens_at'] ?? null),
                'closes_at' => $closed ? null : ($data['closes_at'] ?? null),
                'is_closed' => $closed,
                'reason' => $data['reason'] ?? null,
            ],
        );
    }
}
