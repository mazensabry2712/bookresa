<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use LogicException;
use RuntimeException;

final class StartBookingPayment
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PaymentService $payments,
        private readonly PaymentGateway $gateway,
        private readonly PlatformSettings $platformSettings,
    ) {}

    public function handle(Booking $booking): Payment
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking must belong to the current tenant.');
        }

        $booking->loadMissing(['customer', 'service']);

        $email = trim((string) data_get($booking->customer, 'email', ''));

        if ($email === '') {
            throw new RuntimeException('Email is required for online payment.');
        }

        $service = $booking->service;
        $servicePriceMinor = (int) ($booking->service_price_minor_snapshot ?? $service->price_minor ?? 0);
        $serviceCurrency = (string) ($booking->service_currency_snapshot ?: ($service->currency ?? ''));

        if ($service === null || $servicePriceMinor <= 0) {
            throw new RuntimeException('This booking cannot be paid online.');
        }

        $centralizedPaymentsEnabled = filter_var(
            $this->platformSettings->get('centralized_booking_payments_enabled', false),
            FILTER_VALIDATE_BOOL,
        );

        if (! $centralizedPaymentsEnabled) {
            throw new RuntimeException('Online booking payments are not enabled for this platform yet.');
        }

        $provider = (string) config('bookresa.payments.default_provider', 'kashier');

        // Centralized checkout always charges the entire booking amount.
        $amountMinor = $servicePriceMinor;
        $metadata = [
            'booking_reference' => $booking->booking_reference,
            'customer_reference' => 'customer-'.$booking->customer_id,
            'customer_email' => $email,
            'payment_mode' => 'full',
            'centralized_payment' => true,
            'merchant_redirect' => route('payments.kashier.return'),
        ];

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
                    && (
                        $latestPayment->expires_at === null
                        || $latestPayment->expires_at->isFuture()
                    )
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
                    amountMinor: $amountMinor,
                    currency: $serviceCurrency,
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
            amountMinor: $amountMinor,
            currency: $serviceCurrency,
            provider: $provider,
            description: 'Booking '.$booking->booking_reference,
            metadata: $metadata,
            idempotencyKey: 'booking-'.$booking->id.'-'.$provider.'-attempt-'.$attempt,
        );
    }
}
