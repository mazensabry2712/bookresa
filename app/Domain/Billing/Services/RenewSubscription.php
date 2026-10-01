<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class RenewSubscription
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly BillingCycle $billingCycle,
        private readonly PlanPricingSnapshot $pricingSnapshots,
    ) {
    }

    public function handle(Subscription $subscription, ?CarbonImmutable $startAt = null): Subscription
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $subscription->tenant_id !== $tenantId) {
            throw new LogicException('Subscription must belong to the current tenant.');
        }

        $startAt ??= CarbonImmutable::now('UTC');

        return DB::transaction(function () use ($subscription, $startAt): Subscription {
            $subscription = Subscription::query()
                ->with('plan')
                ->whereKey($subscription->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($subscription->status !== SubscriptionStatus::Expired) {
                throw new RuntimeException('Only an expired subscription can be renewed in this MVP flow.');
            }

            $plan = $subscription->nextPlan ?? $subscription->plan;

            if ($plan === null || ! $plan->is_active) {
                throw new RuntimeException('The renewal plan is not available.');
            }

            $snapshot = $this->pricingSnapshots->make($plan);
            $endAt = $this->billingCycle->endAt($startAt, $plan->billing_period);

            $subscription->forceFill([
                'plan_id' => $plan->getKey(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => SubscriptionStatus::Active,
                'payment_status' => PaymentStatus::Pending,
                'price_minor' => (int) $plan->price_minor,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
                'included_customer_limit' => (int) $plan->included_customer_limit,
                'additional_customer_price_minor' => (int) $plan->additional_customer_price_minor,
                'pricing_snapshot' => $snapshot,
                'next_plan_id' => null,
                'plan_change_effective_at' => null,
                'cancelled_at' => null,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'renewed_at' => $startAt->toIso8601String(),
                ]),
            ])->save();

            return $subscription->fresh(['plan']);
        });
    }
}
