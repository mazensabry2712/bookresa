<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Data\UsageSummary;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Customer\Models\Customer;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;

final class CalculateSubscriptionUsage
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Subscription $subscription): UsageSummary
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $subscription->tenant_id !== $tenantId) {
            throw new LogicException('Subscription must belong to the current tenant.');
        }

        $uniqueCustomers = Customer::query()->count();
        $included = (int) $subscription->included_customer_limit;
        $additional = max($uniqueCustomers - $included, 0);
        $unitPrice = (int) $subscription->additional_customer_price_minor;
        $usageCharge = $additional * $unitPrice;
        $base = (int) $subscription->price_minor;

        return new UsageSummary(
            uniqueCustomerCount: $uniqueCustomers,
            includedCustomerLimit: $included,
            additionalCustomerCount: $additional,
            additionalCustomerPriceMinor: $unitPrice,
            basePriceMinor: $base,
            usageChargeMinor: $usageCharge,
            totalChargeMinor: $base + $usageCharge,
            currency: (string) $subscription->currency,
        );
    }
}
