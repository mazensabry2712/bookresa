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

        $target = match ($status) {
            PaymentStatus::Paid => BookingPaymentStatus::Paid,
            PaymentStatus::Refunded => BookingPaymentStatus::Refunded,
            default => null,
        };

        if ($target !== null && $booking->payment_status !== $target) {
            $booking->forceFill(['payment_status' => $target])->save();
        }
    }
}
