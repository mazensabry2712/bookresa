<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\BookingCommission;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use LogicException;

final class SyncBookingCommission
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

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
            $lockedPayment = Payment::withoutGlobalScopes()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedPayment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)) {
                return null;
            }

            $snapshot = data_get($lockedPayment->metadata, 'commission_snapshot');

            if (! is_array($snapshot)) {
                return null;
            }

            $eligible = (bool) ($snapshot['eligible'] ?? false);
            $rateBasisPoints = $eligible ? max(0, min(10000, (int) ($snapshot['rate_basis_points'] ?? 0))) : 0;
            $grossAmountMinor = (int) $lockedPayment->amount_minor;
            $refundedAmountMinor = max(0, min($grossAmountMinor, (int) $lockedPayment->refunded_amount_minor));
            $netCollectedMinor = max($grossAmountMinor - $refundedAmountMinor, 0);
            $commissionAmountMinor = min(
                intdiv(($netCollectedMinor * $rateBasisPoints) + 5000, 10000),
                $netCollectedMinor,
            );

            $values = [
                'tenant_id' => $tenantId,
                'payment_id' => (int) $lockedPayment->getKey(),
                'customer_id' => isset($snapshot['customer_id']) ? (int) $snapshot['customer_id'] : null,
                'customer_ordinal' => max(0, (int) ($snapshot['customer_ordinal'] ?? 0)),
                'included_customer_limit' => max(0, (int) ($snapshot['included_customer_limit'] ?? 0)),
                'eligible' => $eligible,
                'rate_basis_points' => max(0, min(10000, (int) ($snapshot['rate_basis_points'] ?? 0))),
                'gross_amount_minor' => $grossAmountMinor,
                'refunded_amount_minor' => $refundedAmountMinor,
                'commission_amount_minor' => $commissionAmountMinor,
                'workspace_net_minor' => $netCollectedMinor - $commissionAmountMinor,
                'currency' => strtoupper($lockedPayment->currency),
                'status' => $netCollectedMinor === 0 ? 'reversed' : 'accrued',
                'metadata' => ['policy_snapshot' => $snapshot],
            ];

            $commission = BookingCommission::withoutGlobalScopes()
                ->where('payment_id', $lockedPayment->getKey())
                ->lockForUpdate()
                ->first();

            if ($commission === null) {
                $commission = new BookingCommission;
            }

            $commission->forceFill($values)->save();

            return $commission->fresh();
        });
    }
}
