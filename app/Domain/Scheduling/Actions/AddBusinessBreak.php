<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Tenant\Services\CurrentTenant;

final class AddBusinessBreak
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(array $data): BusinessBreak
    {
        $tenantId = $this->currentTenant->idOrFail();
        $startsAt = (string) $data['starts_at'];
        $endsAt = (string) $data['ends_at'];

        if ($endsAt <= $startsAt) {
            throw new \InvalidArgumentException('Business break must end after it starts.');
        }

        return BusinessBreak::query()->create([
            'tenant_id' => $tenantId,
            'day_of_week' => (int) $data['day_of_week'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'label' => $data['label'] ?? null,
        ]);
    }
}
