<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class ReactivateSubscription
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

            if (! $subscription->status->isUsable() || $subscription->cancelled_at === null) {
                throw new RuntimeException('Only a scheduled cancellation can be reactivated.');
            }

            $subscription->forceFill([
                'cancelled_at' => null,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'cancel_at_period_end' => false,
                ]),
            ])->save();

            return $subscription->fresh();
        });
    }
}
