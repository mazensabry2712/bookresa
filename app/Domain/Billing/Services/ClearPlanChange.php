<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class ClearPlanChange
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Subscription $subscription): Subscription
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $subscription->tenant_id !== $tenantId) {
            throw new LogicException('Subscription must belong to the current tenant.');
        }

        return DB::transaction(function () use ($subscription): Subscription {
            $subscription = Subscription::query()
                ->whereKey($subscription->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($subscription->next_plan_id === null) {
                throw new RuntimeException('No scheduled plan change exists.');
            }

            $subscription->forceFill([
                'next_plan_id' => null,
                'plan_change_effective_at' => null,
            ])->save();

            return $subscription->fresh();
        });
    }
}
