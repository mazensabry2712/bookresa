<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\UsageChargeType;
use App\Domain\Billing\Enums\UsagePeriodStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Models\UsagePeriod;
use App\Domain\Customer\Models\Customer;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateUsagePeriod
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly CalculateSubscriptionUsage $calculator,
    ) {
    }

    public function handle(Subscription $subscription): UsagePeriod
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $subscription->tenant_id !== $tenantId) {
            throw new RuntimeException('Subscription must belong to the current tenant.');
        }

        return DB::transaction(function () use ($subscription): UsagePeriod {
            $existing = UsagePeriod::query()
                ->where('subscription_id', $subscription->getKey())
                ->where('period_start', $subscription->start_at)
                ->where('period_end', $subscription->end_at)
                ->first();

            if ($existing !== null) {
                return $existing->load('charges');
            }

            $summary = $this->calculator->handle($subscription);

            $period = UsagePeriod::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->getKey(),
                'period_start' => $subscription->start_at,
                'period_end' => $subscription->end_at,
                'unique_customer_count' => $summary->uniqueCustomerCount,
                'included_customer_limit' => $summary->includedCustomerLimit,
                'additional_customer_count' => $summary->additionalCustomerCount,
                'additional_customer_price_minor' => $summary->additionalCustomerPriceMinor,
                'base_price_minor' => $summary->basePriceMinor,
                'usage_charge_minor' => $summary->usageChargeMinor,
                'total_charge_minor' => $summary->totalChargeMinor,
                'currency' => $summary->currency,
                'status' => UsagePeriodStatus::Open,
                'pricing_snapshot' => [
                    'subscription_id' => $subscription->getKey(),
                    'plan_id' => $subscription->plan_id,
                    'price_minor' => $subscription->price_minor,
                    'currency' => $subscription->currency,
                    'billing_period' => $subscription->billing_period->value,
                    'included_customer_limit' => $subscription->included_customer_limit,
                    'additional_customer_price_minor' => $subscription->additional_customer_price_minor,
                ],
            ]);

            if ($summary->additionalCustomerCount > 0) {
                $period->charges()->create([
                    'tenant_id' => $subscription->tenant_id,
                    'type' => UsageChargeType::AdditionalCustomers,
                    'units' => $summary->additionalCustomerCount,
                    'unit_price_minor' => $summary->additionalCustomerPriceMinor,
                    'amount_minor' => $summary->usageChargeMinor,
                    'pricing_snapshot' => [
                        'included_customer_limit' => $summary->includedCustomerLimit,
                        'customer_count' => $summary->uniqueCustomerCount,
                        'unit_price_minor' => $summary->additionalCustomerPriceMinor,
                    ],
                ]);
            }

            return $period->fresh('charges');
        });
    }
}
