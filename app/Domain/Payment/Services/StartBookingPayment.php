<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Domain\Payment\Contracts\PaymentGateway;
use LogicException;
use RuntimeException;

final class StartBookingPayment
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PaymentService $payments,
        private readonly PaymentGateway $gateway,
    ) {
    }

    public function handle(Booking $booking): \App\Domain\Payment\Models\Payment
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking must belong to the current tenant.');
        }

        $booking->loadMissing(['customer', 'service']);

        $email = trim((string) ($booking->customer?->email ?? ''));

        if ($email === '') {
            throw new RuntimeException('Email is required for online payment.');
        }

        $service = $booking->service;

        if ($service === null || (int) $service->price_minor <= 0) {
            throw new RuntimeException('This booking cannot be paid online.');
        }

        $metadata = [
            'booking_reference' => $booking->booking_reference,
            'customer_reference' => 'customer-'.$booking->customer_id,
            'customer_email' => $email,
            'merchant_redirect' => route('payments.kashier.return'),
        ];

        $provider = (string) config('bookresa.payments.default_provider', 'kashier');

        $latestPayment = $booking->payments()
            ->where('provider', $provider)
            ->latest('id')
            ->first();

        if ($latestPayment !== null) {
            if (
                $latestPayment->status === PaymentStatus::Paid
                || (
                    in_array($latestPayment->status, [
                        PaymentStatus::Pending,
                        PaymentStatus::Processing,
                    ], true)
                    && $latestPayment->checkout_url !== null
                )
            ) {
                return $latestPayment;
            }

            if (
                $latestPayment->status === PaymentStatus::Pending
                && $latestPayment->provider_reference === null
                && $latestPayment->idempotency_key !== null
            ) {
                return $this->payments->start(
                    gateway: $this->gateway,
                    payable: $booking,
                    amountMinor: (int) $service->price_minor,
                    currency: (string) $service->currency,
                    provider: $provider,
                    description: 'Booking '.$booking->booking_reference,
                    metadata: $metadata,
                    idempotencyKey: $latestPayment->idempotency_key,
                );
            }
        }

        $attempt = $booking->payments()
            ->where('provider', $provider)
            ->count() + 1;

        return $this->payments->start(
            gateway: $this->gateway,
            payable: $booking,
            amountMinor: (int) $service->price_minor,
            currency: (string) $service->currency,
            provider: $provider,
            description: 'Booking '.$booking->booking_reference,
            metadata: $metadata,
            idempotencyKey: 'booking-'.$booking->id.'-'.$provider.'-attempt-'.$attempt,
        );
    }
}
