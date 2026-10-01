<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class SchedulePlanChange
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Subscription $subscription, Plan $plan): Subscription
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $subscription->tenant_id !== $tenantId) {
            throw new LogicException('Subscription must belong to the current tenant.');
        }

        if (! $plan->is_active) {
            throw new RuntimeException('The selected plan is not active.');
        }

        if ($subscription->plan_id === $plan->id) {
            throw new RuntimeException('The subscription is already on this plan.');
        }

        return DB::transaction(function () use ($subscription, $plan): Subscription {
            $subscription = Subscription::query()
                ->whereKey($subscription->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($subscription->status, [SubscriptionStatus::Trial, SubscriptionStatus::Active], true)) {
                throw new RuntimeException('Only an active subscription can change plans.');
            }

            $effectiveAt = $subscription->end_at;

            $subscription->forceFill([
                'next_plan_id' => $plan->getKey(),
                'plan_change_effective_at' => $effectiveAt,
            ])->save();

            return $subscription->fresh(['nextPlan']);
        });
    }
}
