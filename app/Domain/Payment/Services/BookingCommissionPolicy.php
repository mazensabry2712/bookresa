<?php

namespace App\Domain\Payment\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use LogicException;
use RuntimeException;

final class BookingCommissionPolicy
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PlatformSettings $platformSettings,
    ) {}

    /**
     * Freeze the applicable customer ordinal and commission policy when checkout is created.
     *
     * @return array<string, int|bool|string>
     */
    public function snapshot(Booking $booking): array
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking must belong to the current tenant.');
        }

        $booking->loadMissing('customer');
        $customer = $booking->customer;

        if (! $customer instanceof Customer || (int) $customer->tenant_id !== $tenantId) {
            throw new RuntimeException('A workspace customer is required to calculate booking commission.');
        }

        $customerOrdinal = Customer::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($customer): void {
                $query->where('created_at', '<', $customer->created_at)
                    ->orWhere(function ($query) use ($customer): void {
                        $query->where('created_at', $customer->created_at)
                            ->where('id', '<=', $customer->getKey());
                    });
            })
            ->count();

        $subscription = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Trial->value, SubscriptionStatus::Active->value])
            ->latest('start_at')
            ->first();

        $includedLimit = $subscription?->isUsable()
            ? (int) $subscription->included_customer_limit
            : max(0, (int) $this->platformSettings->get('commission_free_customer_limit', 1000));
        $rateBasisPoints = max(0, min(10000, (int) $this->platformSettings->get('booking_commission_rate_basis_points', 500)));

        return [
            'version' => 'customer-ordinal-v1',
            'customer_id' => (int) $customer->getKey(),
            'customer_ordinal' => $customerOrdinal,
            'included_customer_limit' => $includedLimit,
            'eligible' => $customerOrdinal > $includedLimit,
            'rate_basis_points' => $rateBasisPoints,
        ];
    }
}
