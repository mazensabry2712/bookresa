<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class RemoveBusinessBreak
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(BusinessBreak $break): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $break->tenant_id !== $tenantId) {
            throw new LogicException('Business break must belong to the current tenant.');
        }

        $break->delete();
    }
}
