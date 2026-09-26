<?php

namespace App\Domain\Payment\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;

final class SyncSubscriptionPaymentStatus
{
    public function handle(Payment $payment, PaymentStatus $status): void
    {
        if ($payment->payable_type !== (new Subscription)->getMorphClass()) {
            return;
        }

        $subscription = $payment->payable;

        if (! $subscription instanceof Subscription) {
            return;
        }

        $subscriptionStart = $subscription->start_at?->toIso8601String();
        $paymentSubscriptionStart = data_get($payment->metadata, 'subscription_start');

        if (
            $subscriptionStart !== null
            && filled($paymentSubscriptionStart)
            && (string) $paymentSubscriptionStart !== $subscriptionStart
        ) {
            return;
        }

        $updates = [];

        if ($status === PaymentStatus::Paid) {
            $updates['payment_status'] = PaymentStatus::Paid;

            if ($subscription->status === SubscriptionStatus::Suspended) {
                $updates['status'] = SubscriptionStatus::Active;
            }
        } elseif ($status === PaymentStatus::Refunded) {
            $updates['payment_status'] = PaymentStatus::Refunded;
        } elseif ($status === PaymentStatus::Failed && $subscription->payment_status !== PaymentStatus::Paid) {
            $updates['payment_status'] = PaymentStatus::Failed;
        }

        if ($updates !== []) {
            $subscription->forceFill($updates)->save();
        }
    }
}
