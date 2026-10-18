<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Enums\PaymentStatus as BookingPaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;

final class SyncBookingPaymentStatus
{
    public function handle(Payment $payment, PaymentStatus $status): void
    {
        if ($payment->payable_type !== (new Booking)->getMorphClass()) {
            return;
        }

        $booking = $payment->payable;

        if (! $booking instanceof Booking) {
            return;
        }

        $booking->loadMissing('service');

        $paidMinor = $booking->payments()
            ->whereIn('status', [PaymentStatus::Paid, PaymentStatus::Refunded])
            ->get(['amount_minor', 'refunded_amount_minor', 'status'])
            ->sum(fn (Payment $item): int => $item->status === PaymentStatus::Paid
                ? max((int) $item->amount_minor - (int) $item->refunded_amount_minor, 0)
                : 0);

        if ($status === PaymentStatus::Refunded && $paidMinor <= 0) {
            $booking->forceFill(['payment_status' => BookingPaymentStatus::Refunded])->save();

            return;
        }

        $totalMinor = (int) ($booking->service->price_minor ?? 0);
        $target = $paidMinor <= 0
            ? BookingPaymentStatus::Unpaid
            : ($totalMinor > 0 && $paidMinor < $totalMinor
                ? BookingPaymentStatus::PartiallyPaid
                : BookingPaymentStatus::Paid);

        if ($booking->payment_status !== $target) {
            $booking->forceFill(['payment_status' => $target])->save();
        }
    }
}
