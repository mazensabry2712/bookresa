<?php

namespace App\Domain\Service\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;
use RuntimeException;

final class DeleteService
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Service $service): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $service->tenant_id !== $tenantId) {
            throw new LogicException('Service must belong to the current tenant.');
        }

        if (Booking::query()->where('service_id', $service->getKey())->exists()) {
            throw new RuntimeException('This service cannot be deleted because it has booking history. Deactivate it instead.');
        }

        $service->delete();
    }
}
