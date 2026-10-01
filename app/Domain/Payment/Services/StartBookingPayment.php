<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Infrastructure\Payments\Kashier\KashierGateway;
use LogicException;
use RuntimeException;

final class StartBookingPayment
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PaymentService $payments,
        private readonly KashierGateway $gateway,
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

        return $this->payments->start(
            gateway: $this->gateway,
            payable: $booking,
            amountMinor: (int) $service->price_minor,
            currency: (string) $service->currency,
            provider: 'kashier',
            description: 'Booking '.$booking->booking_reference,
            metadata: $metadata,
            idempotencyKey: 'booking-'.$booking->id.'-kashier',
        );
    }
}
