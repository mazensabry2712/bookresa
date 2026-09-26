<?php

namespace Tests\Fakes;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

final class FakePaymentGateway implements PaymentGateway
{
    public int $createCalls = 0;

    public function createPayment(PaymentRequest $request): PaymentGatewayResult
    {
        $this->createCalls++;

        return new PaymentGatewayResult(
            status: PaymentStatus::Processing,
            providerReference: 'FAKE-'.$request->merchantReference,
            checkoutUrl: 'https://payments.example.test/checkout/'.$request->merchantReference,
            method: 'card',
            metadata: ['gateway' => 'fake'],
        );
    }

    public function verifyPayment(string $providerReference): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            status: PaymentStatus::Paid,
            providerReference: $providerReference,
            method: 'card',
            metadata: ['verified' => true],
            paidAt: CarbonImmutable::parse('2026-09-28 10:00', 'UTC'),
        );
    }

    public function refundPayment(string $providerReference, int $amountMinor): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            status: PaymentStatus::Refunded,
            providerReference: $providerReference,
            method: 'card',
            metadata: ['refunded_amount_minor' => $amountMinor],
        );
    }
}
