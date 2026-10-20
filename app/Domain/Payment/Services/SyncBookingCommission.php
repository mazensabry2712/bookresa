<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\BookingCommission;
use App\Domain\Payment\Models\BookingCommissionAllocation;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use LogicException;

final class SyncBookingCommission
{
    public function __construct(private readonly CurrentTenant $currentTenant)
    {
    }

    public function handle(Payment $payment): ?BookingCommission
    {
        if ($payment->payable_type !== (new Booking)->getMorphClass()) {
            return null;
        }

        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $payment->tenant_id !== $tenantId) {
            throw new LogicException('Booking payment must belong to the current workspace.');
        }

        return DB::transaction(function () use ($payment, $tenantId): ?BookingCommission {
            $currentPayment = Payment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($currentPayment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)) {
                return null;
            }

            $booking = Booking::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($currentPayment->payable_id)
                ->first();

            if (! $booking instanceof Booking) {
                return null;
            }

            // The allocation is a hard cap for all successful attempts against one booking.
            // Serialize reconciliation on this row and recompute all settled attempts to keep
            // duplicate successes, partial refunds and refund replays from double charging.
            $allocation = BookingCommissionAllocation::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $booking->getKey())
                ->lockForUpdate()
                ->first();

            if ($allocation === null) {
                // Payment can settle before business confirmation; confirmation retries reconciliation.
                return null;
            }

            if (strtoupper($currentPayment->currency) !== strtoupper($allocation->currency)) {
                return null;
            }

            $eligible = (bool) $allocation->eligible;
            $rateBasisPoints = $eligible ? max(0, min(10000, (int) $allocation->rate_basis_points)) : 0;
            $commissionCap = (int) $allocation->commission_amount_minor_snapshot;

            $attempts = Payment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('payable_type', (new Booking)->getMorphClass())
                ->where('payable_id', $booking->getKey())
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $remainingCommission = $commissionCap;
            $currentCommission = null;

            foreach ($attempts as $attempt) {
                $grossAmountMinor = max(0, (int) $attempt->amount_minor);
                $refundedAmountMinor = max(0, min($grossAmountMinor, (int) $attempt->refunded_amount_minor));

                if ($attempt->status === PaymentStatus::Refunded && $refundedAmountMinor === 0) {
                    $refundedAmountMinor = $grossAmountMinor;
                }

                $netCollectedMinor = max($grossAmountMinor - $refundedAmountMinor, 0);
                $calculatedCommissionMinor = $eligible && $rateBasisPoints > 0
                    ? min(intdiv(($netCollectedMinor * $rateBasisPoints) + 5000, 10000), $netCollectedMinor)
                    : 0;
                $commissionAmountMinor = min($calculatedCommissionMinor, max(0, $remainingCommission));
                $remainingCommission -= $commissionAmountMinor;

                $snapshot = [
                    'version' => $allocation->policy_version,
                    'allocation_id' => (int) $allocation->getKey(),
                    'period_start' => CarbonImmutable::parse((string) $allocation->period_start)->toDateString(),
                    'period_end' => CarbonImmutable::parse((string) $allocation->period_end)->toDateString(),
                    'timezone' => $allocation->timezone,
                    'booking_ordinal' => (int) $allocation->booking_ordinal,
                    'free_booking_allowance' => (int) $allocation->free_booking_allowance,
                    'commission_enabled' => (bool) $allocation->commission_enabled,
                    'eligible' => $eligible,
                    'rate_basis_points' => $rateBasisPoints,
                    'booking_amount_minor_snapshot' => (int) $allocation->booking_amount_minor_snapshot,
                    'commission_amount_minor_snapshot' => $commissionCap,
                    'currency' => $allocation->currency,
                ];

                $values = [
                    'tenant_id' => $tenantId,
                    'payment_id' => (int) $attempt->getKey(),
                    'booking_commission_allocation_id' => (int) $allocation->getKey(),
                    'booking_ordinal' => (int) $allocation->booking_ordinal,
                    'free_booking_allowance' => (int) $allocation->free_booking_allowance,
                    'commission_policy_version' => $allocation->policy_version,
                    // Keep legacy non-null columns populated while new rows use the booking ordinal.
                    'customer_id' => (int) $booking->customer_id,
                    'customer_ordinal' => 0,
                    'included_customer_limit' => 0,
                    'eligible' => $eligible,
                    'rate_basis_points' => $rateBasisPoints,
                    'gross_amount_minor' => $grossAmountMinor,
                    'refunded_amount_minor' => $refundedAmountMinor,
                    'commission_amount_minor' => $commissionAmountMinor,
                    'workspace_net_minor' => $netCollectedMinor - $commissionAmountMinor,
                    'currency' => strtoupper($attempt->currency),
                    'status' => $netCollectedMinor === 0 ? 'reversed' : 'accrued',
                    'metadata' => ['policy_snapshot' => $snapshot],
                ];

                $commission = BookingCommission::withoutGlobalScopes()
                    ->where('payment_id', $attempt->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($commission === null) {
                    $commission = new BookingCommission;
                }

                $commission->forceFill($values)->save();

                if ((int) $attempt->getKey() === (int) $currentPayment->getKey()) {
                    $currentCommission = $commission;
                }
            }

            return $currentCommission?->fresh();
        }, 3);
    }
}
