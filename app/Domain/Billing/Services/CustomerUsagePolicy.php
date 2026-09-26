<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Tenant\Services\CurrentTenant;

final class CustomerUsagePolicy
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function allowsCreation(): bool
    {
        $tenant = $this->currentTenant->get();

        if ($tenant === null) {
            return false;
        }

        $policy = (string) data_get(
            BusinessProfile::query()->where('tenant_id', $tenant->getKey())->first()?->booking_settings,
            'customer_limit_policy',
            'allow_overage',
        );

        if ($policy !== 'block_new_customers') {
            return true;
        }

        $subscription = Subscription::query()
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->first();

        if ($subscription === null) {
            return true;
        }

        return CustomerUsageGuard::countCustomers($tenant->getKey()) < $subscription->included_customer_limit;
    }
}
