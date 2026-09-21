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

final class CreateSubscription
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly BillingCycle $billingCycle,
        private readonly PlanPricingSnapshot $pricingSnapshots,
    ) {
    }

    public function handle(Plan $plan, ?CarbonImmutable $startAt = null): Subscription
    {
        $tenantId = $this->currentTenant->idOrFail();

        if (! $plan->is_active) {
            throw new RuntimeException('The selected plan is not active.');
        }

        $startAt ??= CarbonImmutable::now('UTC');

        return DB::transaction(function () use ($plan, $tenantId, $startAt): Subscription {
            $existing = Subscription::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', [
                    SubscriptionStatus::Trial->value,
                    SubscriptionStatus::Active->value,
                ])
                ->where('end_at', '>', $startAt)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new LogicException('The tenant already has an active subscription.');
            }

            $trialDays = (int) $plan->trial_days;
            $status = $trialDays > 0
                ? SubscriptionStatus::Trial
                : SubscriptionStatus::Active;

            $endAt = $trialDays > 0
                ? $startAt->addDays($trialDays)
                : $this->billingCycle->endAt($startAt, $plan->billing_period);

            $snapshot = $this->pricingSnapshots->make($plan);

            return Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->getKey(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $status,
                'payment_status' => $status === SubscriptionStatus::Trial
                    ? PaymentStatus::Paid
                    : PaymentStatus::Pending,
                'price_minor' => (int) $plan->price_minor,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
                'included_customer_limit' => (int) $plan->included_customer_limit,
                'additional_customer_price_minor' => (int) $plan->additional_customer_price_minor,
                'pricing_snapshot' => $snapshot,
                'metadata' => [
                    'created_from_plan_version' => $plan->updated_at?->toIso8601String(),
                ],
            ]);
        });
    }
}
