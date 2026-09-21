<?php

namespace App\Domain\Billing\Data;

readonly class UsageSummary
{
    public function __construct(
        public int $uniqueCustomerCount,
        public int $includedCustomerLimit,
        public int $additionalCustomerCount,
        public int $additionalCustomerPriceMinor,
        public int $basePriceMinor,
        public int $usageChargeMinor,
        public int $totalChargeMinor,
        public string $currency,
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'unique_customer_count' => $this->uniqueCustomerCount,
            'included_customer_limit' => $this->includedCustomerLimit,
            'additional_customer_count' => $this->additionalCustomerCount,
            'additional_customer_price_minor' => $this->additionalCustomerPriceMinor,
            'base_price_minor' => $this->basePriceMinor,
            'usage_charge_minor' => $this->usageChargeMinor,
            'total_charge_minor' => $this->totalChargeMinor,
            'currency' => $this->currency,
        ];
    }
}
