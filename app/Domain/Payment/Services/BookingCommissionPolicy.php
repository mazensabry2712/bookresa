<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Models\BookingCommissionAllocation;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;
use RuntimeException;

/**
 * Compatibility adapter for code that needs a policy snapshot.
 * Eligibility is allocated on booking confirmation, never inferred from customer profiles at checkout.
 */
final class BookingCommissionPolicy
{
    public function __construct(private readonly CurrentTenant $currentTenant)
    {
    }

    /** @return array<string, int|bool|string> */
    public function snapshot(Booking $booking): array
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking commission snapshot must use the current workspace.');
        }

        $allocation = BookingCommissionAllocation::query()
            ->where('booking_id', $booking->getKey())
            ->first();

        if ($allocation === null) {
            throw new RuntimeException('Monthly booking commission is allocated when the booking is first confirmed.');
        }

        return [
            'version' => $allocation->policy_version,
            'allocation_id' => (int) $allocation->getKey(),
            'period_start' => $allocation->period_start->toDateString(),
            'period_end' => $allocation->period_end->toDateString(),
            'timezone' => $allocation->timezone,
            'booking_ordinal' => (int) $allocation->booking_ordinal,
            'free_booking_allowance' => (int) $allocation->free_booking_allowance,
            'commission_enabled' => (bool) $allocation->commission_enabled,
            'eligible' => (bool) $allocation->eligible,
            'rate_basis_points' => (int) $allocation->rate_basis_points,
            'booking_amount_minor_snapshot' => (int) $allocation->booking_amount_minor_snapshot,
            'commission_amount_minor_snapshot' => (int) $allocation->commission_amount_minor_snapshot,
            'currency' => $allocation->currency,
        ];
    }
}
