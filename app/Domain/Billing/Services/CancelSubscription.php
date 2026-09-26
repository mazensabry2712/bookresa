<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class CancelSubscription
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly AuditLogger $audit,
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

            if ($subscription->status->isUsable()) {
                $subscription->forceFill([
                    'cancelled_at' => $subscription->end_at,
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'cancel_at_period_end' => true,
                    ]),
                ])->save();
            } elseif ($subscription->status !== SubscriptionStatus::Cancelled) {
                throw new RuntimeException('Only an active or trial subscription can be cancelled.');
            }

            $fresh = $subscription->fresh();

            if ($fresh->cancelled_at !== null) {
                $this->audit->log(
                    'subscription.cancellation_scheduled',
                    $fresh,
                    ['tenant_id' => (int) $fresh->tenant_id, 'subscription_id' => (int) $fresh->getKey()],
                );
            }

            return $fresh;
        });
    }
}
